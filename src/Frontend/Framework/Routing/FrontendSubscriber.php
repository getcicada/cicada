<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Routing;

use Cicada\Core\Checkout\Cart\Exception\MemberNotLoggedInException;
use Cicada\Frontend\Member\Event\MemberLoginEvent;
use Cicada\Frontend\Member\Event\MemberLogoutEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\Event\ChannelContextResolvedEvent;
use Cicada\Core\Framework\Routing\Exception\MemberNotLoggedInRoutingException;
use Cicada\Core\Framework\Routing\KernelListenerPriorities;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\Framework\Util\Random;
use Cicada\Core\PlatformRequest;
use Cicada\Core\ChannelRequest;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('frontend')]
class FrontendSubscriber implements EventSubscriberInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly RouterInterface $router,
        private readonly MaintenanceModeResolver $maintenanceModeResolver,
        private readonly SystemConfigService $systemConfigService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['startSession', 40],
                ['maintenanceResolver'],
            ],
            KernelEvents::EXCEPTION => [
                ['memberNotLoggedInHandler'],
                ['maintenanceResolver'],
            ],
            KernelEvents::CONTROLLER => [
                ['preventPageLoadingFromXmlHttpRequest', KernelListenerPriorities::KERNEL_CONTROLLER_EVENT_SCOPE_VALIDATE],
            ],
            MemberLoginEvent::class => [
                'updateSessionAfterLogin',
            ],
            MemberLogoutEvent::class => [
                'updateSessionAfterLogout',
            ],
            ChannelContextResolvedEvent::class => [
                ['replaceContextToken'],
            ],
        ];
    }

    public function startSession(): void
    {
        $master = $this->requestStack->getMainRequest();

        if (!$master) {
            return;
        }
        if (!$master->attributes->get(ChannelRequest::ATTRIBUTE_IS_SALES_CHANNEL_REQUEST)) {
            return;
        }

        if (!$master->hasSession()) {
            return;
        }

        $session = $master->getSession();

        if (!$session->isStarted()) {
            $session->setName('session-');
            $session->start();
            $session->set('sessionId', $session->getId());
        }

        $channelId = $master->attributes->get(PlatformRequest::ATTRIBUTE_CHANNEL_ID);
        if ($channelId === null) {
            /** @var ChannelContext|null $channelContext */
            $channelContext = $master->attributes->get(PlatformRequest::ATTRIBUTE_CHANNEL_CONTEXT_OBJECT);
            if ($channelContext !== null) {
                $channelId = $channelContext->getChannel()->getId();
            }
        }

        if ($this->shouldRenewToken($session, $channelId)) {
            $token = Random::getAlphanumericString(32);
            $session->set(PlatformRequest::HEADER_CONTEXT_TOKEN, $token);
            $session->set(PlatformRequest::ATTRIBUTE_CHANNEL_ID, $channelId);
        }

        $master->headers->set(
            PlatformRequest::HEADER_CONTEXT_TOKEN,
            $session->get(PlatformRequest::HEADER_CONTEXT_TOKEN)
        );
    }

    public function updateSessionAfterLogin(MemberLoginEvent $event): void
    {
        $token = $event->getContextToken();

        $this->updateSession($token);
    }

    public function updateSessionAfterLogout(): void
    {
        $newToken = Random::getAlphanumericString(32);

        $this->updateSession($newToken, true);
    }

    public function updateSession(string $token, bool $destroyOldSession = false): void
    {
        $master = $this->requestStack->getMainRequest();
        if (!$master) {
            return;
        }
        if (!$master->attributes->get(ChannelRequest::ATTRIBUTE_IS_SALES_CHANNEL_REQUEST)) {
            return;
        }

        if (!$master->hasSession()) {
            return;
        }

        $session = $master->getSession();
        $session->migrate($destroyOldSession);
        $session->set('sessionId', $session->getId());

        $session->set(PlatformRequest::HEADER_CONTEXT_TOKEN, $token);
        $master->headers->set(PlatformRequest::HEADER_CONTEXT_TOKEN, $token);
    }

    public function memberNotLoggedInHandler(ExceptionEvent $event): void
    {
        if (!$event->getRequest()->attributes->has(ChannelRequest::ATTRIBUTE_IS_SALES_CHANNEL_REQUEST)) {
            return;
        }

        if (!$this->shouldRedirectLoginPage($event->getThrowable())) {
            return;
        }

        $request = $event->getRequest();

        $parameters = [
            'redirectTo' => $request->attributes->get('_route'),
            'redirectParameters' => json_encode($request->attributes->get('_route_params'), \JSON_THROW_ON_ERROR),
        ];

        $redirectResponse = new RedirectResponse($this->router->generate('frontend.account.login.page', $parameters));

        $event->setResponse($redirectResponse);
    }

    public function maintenanceResolver(RequestEvent $event): void
    {
        if ($this->maintenanceModeResolver->shouldRedirect($event->getRequest())) {
            $event->setResponse(
                new RedirectResponse(
                    $this->router->generate('frontend.maintenance.page'),
                    RedirectResponse::HTTP_TEMPORARY_REDIRECT
                )
            );
        }
    }

    public function preventPageLoadingFromXmlHttpRequest(ControllerEvent $event): void
    {
        if (!$event->getRequest()->isXmlHttpRequest()) {
            return;
        }

        /** @var list<string> $scope */
        $scope = $event->getRequest()->attributes->get(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, []);

        if (!\in_array(FrontendRouteScope::ID, $scope, true)) {
            return;
        }

        $isAllowed = $event->getRequest()->attributes->getBoolean('XmlHttpRequest');

        if ($isAllowed) {
            return;
        }

        throw RoutingException::accessDeniedForXmlHttpRequest();
    }

    // used to switch session token - when the context token expired
    public function replaceContextToken(ChannelContextResolvedEvent $event): void
    {
        $context = $event->getChannelContext();

        // only update session if token expired and switched
        if ($event->getUsedToken() === $context->getToken()) {
            return;
        }

        $this->updateSession($context->getToken());
    }

    private function shouldRenewToken(SessionInterface $session, ?string $channelId = null): bool
    {
        if (!$session->has(PlatformRequest::HEADER_CONTEXT_TOKEN) || $channelId === null) {
            return true;
        }

        if ($this->systemConfigService->get('core.systemWideLoginRegistration.isMemberBoundToChannel')) {
            return $session->get(PlatformRequest::ATTRIBUTE_CHANNEL_ID) !== $channelId;
        }

        return false;
    }

    private function shouldRedirectLoginPage(\Throwable $ex): bool
    {
        if ($ex instanceof MemberNotLoggedInRoutingException) {
            return true;
        }

        if ($ex instanceof MemberNotLoggedInException) {
            return true;
        }

        return false;
    }
}
