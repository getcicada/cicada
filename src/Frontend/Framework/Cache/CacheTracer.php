<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Cache;

use Cicada\Core\Framework\Adapter\Cache\AbstractCacheTracer;
use Cicada\Core\Framework\Log\Package;
use Cicada\Frontend\Theme\ThemeConfigValueAccessor;

/**
 * @internal
 *
 * @extends AbstractCacheTracer<mixed|null>
 */
#[Package('core')]
class CacheTracer extends AbstractCacheTracer
{
    /**
     * @internal
     *
     * @param AbstractCacheTracer<mixed|null> $decorated
     */
    public function __construct(
        private readonly AbstractCacheTracer $decorated,
        private readonly ThemeConfigValueAccessor $themeConfigAccessor
    ) {
    }

    public function getDecorated(): AbstractCacheTracer
    {
        return $this->decorated;
    }

    public function trace(string $key, \Closure $param)
    {
        return $this->themeConfigAccessor->trace($key, fn () => $this->getDecorated()->trace($key, $param));
    }

    public function get(string $key): array
    {
        return array_unique(array_merge(
            $this->themeConfigAccessor->getTrace($key),
            $this->getDecorated()->get($key)
        ));
    }
}