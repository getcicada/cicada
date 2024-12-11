<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Adapter\Cache\Http;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\MaintenanceModeResolver;
use Cicada\Core\PlatformRequest;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
#[Package('core')]
class CacheResponseSubscriber implements EventSubscriberInterface
{
    /**
     * @param array<string> $cookies
     *
     * @internal
     */
    public function __construct(
        private readonly int                      $defaultTtl,
        private readonly bool                     $httpCacheEnabled,
        private readonly MaintenanceModeResolver  $maintenanceResolver,
        private readonly ?string                  $staleWhileRevalidate,
        private readonly ?string                  $staleIfError,
    )
    {
    }

    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [
                ['setResponseCache', -1500],
                ['setResponseCacheHeader', 1500],
            ],
        ];
    }

    public function setResponseCache(ResponseEvent $event): void
    {
        if (!$this->httpCacheEnabled) {
            return;
        }

        $response = $event->getResponse();

        $request = $event->getRequest();

        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_CHANNEL_CONTEXT_OBJECT);

        if (!$context instanceof ChannelContext) {
            return;
        }

        if (!$this->maintenanceResolver->shouldBeCached($request)) {
            return;
        }

        if ($response->getStatusCode() === Response::HTTP_NOT_FOUND) {
            // 404 pages should not be cached by reverse proxy, as the cache hit rate would be super low,
            // and there is no way to invalidate once the url becomes available
            // To still be able to serve 404 pages fast, we don't load the full context and cache the rendered html on application side
            // as we don't have the full context the state handling is broken as no member or cart is available, even if the member is logged in
            // @see \Cicada\Frontend\Framework\Routing\NotFound\NotFoundSubscriber::onError
            return;
        }

        $route = $request->attributes->get('_route');

        // We need to allow it on login, otherwise the state is wrong
        if (!($route === 'frontend.account.login' || $request->getMethod() === Request::METHOD_GET)) {
            return;
        }

        if ($request->cookies->has(HttpCacheKeyGenerator::CONTEXT_CACHE_COOKIE)) {
            $response->headers->removeCookie(HttpCacheKeyGenerator::CONTEXT_CACHE_COOKIE);
            $response->headers->clearCookie(HttpCacheKeyGenerator::CONTEXT_CACHE_COOKIE);
        }

        /** @var bool|array{maxAge?: int, states?: list<string>}|null $cache */
        $cache = $request->attributes->get(PlatformRequest::ATTRIBUTE_HTTP_CACHE);
        if (!$cache) {
            return;
        }

        if ($cache === true) {
            $cache = [];
        }

        if ($this->hasInvalidationState($cache['states'] ?? [], $states)) {
            return;
        }

        $maxAge = $cache['maxAge'] ?? $this->defaultTtl;

        $response->setSharedMaxAge($maxAge);
        $response->headers->set(
            HttpCacheKeyGenerator::INVALIDATION_STATES_HEADER,
            implode(',', $cache['states'] ?? [])
        );

        if ($this->staleIfError !== null) {
            $response->headers->addCacheControlDirective('stale-if-error', $this->staleIfError);
        }

        if ($this->staleWhileRevalidate !== null) {
            $response->headers->addCacheControlDirective('stale-while-revalidate', $this->staleWhileRevalidate);
        }
    }

    public function setResponseCacheHeader(ResponseEvent $event): void
    {
        if (!$this->httpCacheEnabled) {
            return;
        }

        $response = $event->getResponse();

        $request = $event->getRequest();

        /** @var bool|array{maxAge?: int, states?: list<string>}|null $cache */
        $cache = $request->attributes->get(PlatformRequest::ATTRIBUTE_HTTP_CACHE);
        if (!$cache) {
            return;
        }

        $response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, '1');
    }

    /**
     * @param list<string> $cacheStates
     * @param list<string> $states
     */
    private function hasInvalidationState(array $cacheStates, array $states): bool
    {
        foreach ($states as $state) {
            if (\in_array($state, $cacheStates, true)) {
                return true;
            }
        }

        return false;
    }
}
