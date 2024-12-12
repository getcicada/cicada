<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\Channel;

use Cicada\Core\Content\Sitemap\Event\SitemapRouteCacheKeyEvent;
use Cicada\Core\Content\Sitemap\Event\SitemapRouteCacheTagsEvent;
use Cicada\Core\Content\Sitemap\Service\SitemapExporterInterface;
use Cicada\Core\Framework\Adapter\Cache\AbstractCacheTracer;
use Cicada\Core\Framework\Adapter\Cache\CacheValueCompressor;
use Cicada\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGenerator;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Util\Hasher;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @deprecated tag:v6.7.0 - reason:decoration-will-be-removed - Will be removed
 */
#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('services-settings')]
class CachedSitemapRoute extends AbstractSitemapRoute
{
    final public const ALL_TAG = 'sitemap-route';

    /**
     * @internal
     *
     * @param AbstractCacheTracer<SitemapRouteResponse> $tracer
     * @param array<string> $states
     */
    public function __construct(
        private readonly AbstractSitemapRoute $decorated,
        private readonly CacheInterface $cache,
        private readonly EntityCacheKeyGenerator $generator,
        private readonly AbstractCacheTracer $tracer,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly array $states,
        private readonly SystemConfigService $config
    ) {
    }

    public static function buildName(string $id): string
    {
        return 'sitemap-route-' . $id;
    }

    public function getDecorated(): AbstractSitemapRoute
    {
        return $this->decorated;
    }

    #[Route(path: '/store-api/sitemap', name: 'store-api.sitemap', methods: ['GET', 'POST'])]
    public function load(Request $request, ChannelContext $context): SitemapRouteResponse
    {
        if (Feature::isActive('cache_rework')) {
            return $this->getDecorated()->load($request, $context);
        }
        if ($context->hasState(...$this->states)) {
            return $this->getDecorated()->load($request, $context);
        }

        $strategy = $this->config->getInt('core.sitemap.sitemapRefreshStrategy');
        if ($strategy === SitemapExporterInterface::STRATEGY_LIVE) {
            return $this->getDecorated()->load($request, $context);
        }

        $key = $this->generateKey($request, $context);

        if ($key === null) {
            return $this->getDecorated()->load($request, $context);
        }

        $value = $this->cache->get($key, function (ItemInterface $item) use ($request, $context) {
            $name = self::buildName($context->getChannelId());

            $response = $this->tracer->trace($name, fn () => $this->getDecorated()->load($request, $context));

            $item->tag($this->generateTags($response, $request, $context));

            return CacheValueCompressor::compress($response);
        });

        return CacheValueCompressor::uncompress($value);
    }

    private function generateKey(Request $request, ChannelContext $context): ?string
    {
        $parts = [$this->generator->getChannelContextHash($context)];

        $event = new SitemapRouteCacheKeyEvent($parts, $request, $context, null);
        $this->dispatcher->dispatch($event);

        if (!$event->shouldCache()) {
            return null;
        }

        return self::buildName($context->getChannelId()) . '-' . Hasher::hash($event->getParts());
    }

    /**
     * @return array<string>
     */
    private function generateTags(SitemapRouteResponse $response, Request $request, ChannelContext $context): array
    {
        $tags = array_merge(
            $this->tracer->get(self::buildName($context->getChannelId())),
            [self::buildName($context->getChannelId()), self::ALL_TAG]
        );

        $event = new SitemapRouteCacheTagsEvent($tags, $request, $response, $context, null);
        $this->dispatcher->dispatch($event);

        return array_unique(array_filter($event->getTags()));
    }
}
