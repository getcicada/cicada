<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Routing;

use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\Event\ChannelContextResolvedEvent;
use Cicada\Core\Framework\Util\Random;
use Cicada\Core\PlatformRequest;
use Cicada\Core\ChannelRequest;
use Cicada\Core\System\Channel\Context\ChannelContextServiceInterface;
use Cicada\Core\System\Channel\Context\ChannelContextServiceParameters;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

#[Package('core')]
class ChannelRequestContextResolver implements RequestContextResolverInterface
{
    use RouteScopeCheckTrait;

    /**
     * @internal
     */
    public function __construct(
        private readonly RequestContextResolverInterface $decorated,
        private readonly ChannelContextServiceInterface $contextService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RouteScopeRegistry $routeScopeRegistry
    ) {
    }

    public function resolve(Request $request): void
    {
        if (!$request->attributes->has(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID)) {
            $this->decorated->resolve($request);

            return;
        }

        if (!$this->isRequestScoped($request, ChannelContextRouteScopeDependant::class)) {
            return;
        }

        if (!$request->headers->has(PlatformRequest::HEADER_CONTEXT_TOKEN)) {
            if ($this->contextTokenRequired($request)) {
                throw RoutingException::missingRequestParameter(PlatformRequest::HEADER_CONTEXT_TOKEN);
            }

            $request->headers->set(PlatformRequest::HEADER_CONTEXT_TOKEN, Random::getAlphanumericString(32));
        }

        $session = $request->hasSession() ? $request->getSession() : null;

        // Retrieve context for current request
        $usedContextToken = (string) $request->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN);
        $contextServiceParameters = new ChannelContextServiceParameters(
            (string) $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID),
            $usedContextToken,
            $request->headers->get(PlatformRequest::HEADER_LANGUAGE_ID),
            $request->attributes->get(ChannelRequest::ATTRIBUTE_DOMAIN_CURRENCY_ID),
            $request->attributes->get(ChannelRequest::ATTRIBUTE_DOMAIN_ID),
            $request->attributes->get(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT),
            null,
            $session?->get(PlatformRequest::ATTRIBUTE_IMITATING_USER_ID)
        );
        $context = $this->contextService->get($contextServiceParameters);

        // Remove imitating user id from session, if there is no member
        if ($session && $context->getImitatingUserId() && !$context->getMemberId()) {
            $session->remove(PlatformRequest::ATTRIBUTE_IMITATING_USER_ID);
            $context->setImitatingUserId(null);
        }

        // Validate if a member login is required for the current request
        $this->validateLogin($request, $context);

        // Update attributes and headers of the current request
        $request->attributes->set(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT, $context->getContext());
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT, $context);
        $request->headers->set(PlatformRequest::HEADER_CONTEXT_TOKEN, $context->getToken());

        $this->eventDispatcher->dispatch(
            new ChannelContextResolvedEvent($context, $usedContextToken)
        );
    }

    /**
     * @deprecated tag:v6.7.0 - Not used anymore, will be removed without replacement
     */
    public function handleChannelContext(Request $request, string $channelId, string $contextToken): void
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            'ChannelRequestContextResolver::handleChannelContext does not need to be called anymore. Will be removed with no replacement',
        );

        $language = $request->headers->get(PlatformRequest::HEADER_LANGUAGE_ID);
        $currencyId = $request->attributes->get(ChannelRequest::ATTRIBUTE_DOMAIN_CURRENCY_ID);

        $context = $this->contextService
            ->get(new ChannelContextServiceParameters($channelId, $contextToken, $language, $currencyId));

        $request->attributes->set(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT, $context->getContext());
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT, $context);
    }

    protected function getScopeRegistry(): RouteScopeRegistry
    {
        return $this->routeScopeRegistry;
    }

    private function contextTokenRequired(Request $request): bool
    {
        return (bool) $request->attributes->get(PlatformRequest::ATTRIBUTE_CONTEXT_TOKEN_REQUIRED, false);
    }

    private function validateLogin(Request $request, ChannelContext $context): void
    {
        if (!$request->attributes->get(PlatformRequest::ATTRIBUTE_LOGIN_REQUIRED)) {
            return;
        }

        if ($context->getMember() === null) {
            throw RoutingException::memberNotLoggedIn();
        }

        if ($request->attributes->get(PlatformRequest::ATTRIBUTE_LOGIN_REQUIRED_ALLOW_GUEST, false) === false && $context->getMember()->getGuest()) {
            throw RoutingException::memberNotLoggedIn();
        }
    }
}
