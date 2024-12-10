<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme;

use Cicada\Core\Framework\Adapter\Cache\CacheValueCompressor;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Util\Hasher;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Package('frontend')]
class CachedResolvedConfigLoader extends AbstractResolvedConfigLoader
{
    /**
     * @internal
     */
    public function __construct(
        private readonly AbstractResolvedConfigLoader $decorated,
        private readonly CacheInterface $cache
    ) {
    }

    public function getDecorated(): AbstractResolvedConfigLoader
    {
        return $this->decorated;
    }

    public function load(string $themeId, ChannelContext $context): array
    {
        $name = self::buildName($themeId);

        $key = Hasher::hash($name . $context->getChannelId() . $context->getDomainId());

        $value = $this->cache->get($key, function (ItemInterface $item) use ($name, $themeId, $context) {
            $config = $this->getDecorated()->load($themeId, $context);

            $item->tag([$name]);

            return CacheValueCompressor::compress($config);
        });

        return CacheValueCompressor::uncompress($value);
    }

    public static function buildName(string $themeId): string
    {
        return 'theme-config-' . $themeId;
    }
}