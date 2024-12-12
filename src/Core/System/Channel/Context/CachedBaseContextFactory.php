<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Context;

use Cicada\Core\Framework\Adapter\Cache\AbstractCacheTracer;
use Cicada\Core\Framework\Adapter\Cache\CacheValueCompressor;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Util\Hasher;
use Cicada\Core\System\Channel\BaseContext;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @internal
 */
#[Package('core')]
class CachedBaseContextFactory extends AbstractBaseContextFactory
{
    /**
     * @param AbstractCacheTracer<BaseContext> $tracer
     */
    public function __construct(
        private readonly AbstractBaseContextFactory $decorated,
        private readonly CacheInterface $cache,
        private readonly AbstractCacheTracer $tracer
    ) {
    }

    public function create(string $channelId, array $options = []): BaseContext
    {
        if (isset($options[ChannelContextService::ORIGINAL_CONTEXT])) {
            return $this->decorated->create($channelId, $options);
        }
        if (isset($options[ChannelContextService::PERMISSIONS])) {
            return $this->decorated->create($channelId, $options);
        }

        $name = self::buildName($channelId);

        ksort($options);

        $keys = \array_intersect_key($options, [
            ChannelContextService::LANGUAGE_ID => true,
            ChannelContextService::DOMAIN_ID => true,
            ChannelContextService::VERSION_ID => true,
        ]);

        $key = implode('-', [$name, Hasher::hash($keys)]);

        $value = $this->cache->get($key, function (ItemInterface $item) use ($name, $channelId, $options) {
            if (Feature::isActive('cache_rework')) {
                $item->tag([$name, CachedChannelContextFactory::ALL_TAG]);

                return CacheValueCompressor::compress(
                    $this->decorated->create($channelId, $options)
                );
            }

            $context = $this->tracer->trace($name, fn () => $this->decorated->create($channelId, $options));

            $keys = array_unique(array_merge(
                $this->tracer->get($name),
                [$name, CachedChannelContextFactory::ALL_TAG]
            ));

            $item->tag($keys);

            return CacheValueCompressor::compress($context);
        });

        return CacheValueCompressor::uncompress($value);
    }

    public static function buildName(string $channelId): string
    {
        return 'base-context-factory-' . $channelId;
    }
}
