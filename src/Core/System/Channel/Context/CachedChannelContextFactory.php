<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Context;

use Cicada\Core\Framework\Adapter\Cache\AbstractCacheTracer;
use Cicada\Core\Framework\Adapter\Cache\CacheValueCompressor;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Util\Hasher;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Package('core')]
class CachedChannelContextFactory extends AbstractChannelContextFactory
{
    final public const ALL_TAG = 'sales-channel-context';

    /**
     * @internal
     *
     * @param AbstractCacheTracer<ChannelContext> $tracer
     */
    public function __construct(
        private readonly AbstractChannelContextFactory $decorated,
        private readonly CacheInterface $cache,
        private readonly AbstractCacheTracer $tracer
    ) {
    }

    public function getDecorated(): AbstractChannelContextFactory
    {
        return $this->decorated;
    }

    public function create(string $token, string $channelId, array $options = []): ChannelContext
    {
        $name = self::buildName($channelId);

        if (!$this->isCacheable($options)) {
            return $this->getDecorated()->create($token, $channelId, $options);
        }

        ksort($options);

        $key = implode('-', [$name, Hasher::hash($options)]);

        $value = $this->cache->get($key, function (ItemInterface $item) use ($name, $token, $channelId, $options) {
            if (Feature::isActive('cache_rework')) {
                $item->tag([$name, self::ALL_TAG]);

                return CacheValueCompressor::compress(
                    $this->decorated->create($token, $channelId, $options)
                );
            }

            $context = $this->tracer->trace($name, fn () => $this->getDecorated()->create($token, $channelId, $options));

            $keys = array_unique(array_merge(
                $this->tracer->get($name),
                [$name, self::ALL_TAG]
            ));

            $item->tag($keys);

            return CacheValueCompressor::compress($context);
        });

        $context = CacheValueCompressor::uncompress($value);

        if (!$context instanceof ChannelContext) {
            return $this->getDecorated()->create($token, $channelId, $options);
        }

        $context->assign(['token' => $token]);

        return $context;
    }

    public static function buildName(string $channelId): string
    {
        return 'context-factory-' . $channelId;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function isCacheable(array $options): bool
    {
        return !isset($options[ChannelContextService::Member_ID])
            && !isset($options[ChannelContextService::BILLING_ADDRESS_ID])
            && !isset($options[ChannelContextService::SHIPPING_ADDRESS_ID]);
    }
}
