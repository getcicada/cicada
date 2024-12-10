<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme;

use Cicada\Core\Framework\Adapter\Cache\CacheInvalidator;
use Cicada\Core\Framework\Adapter\Translation\Translator;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Frontend\Framework\Routing\CachedDomainLoader;
use Cicada\Frontend\Theme\Event\ThemeAssignedEvent;
use Cicada\Frontend\Theme\Event\ThemeConfigChangedEvent;
use Cicada\Frontend\Theme\Event\ThemeConfigResetEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('frontend')]
class CachedResolvedConfigLoaderInvalidator implements EventSubscriberInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly CacheInvalidator $cacheInvalidator,
        private readonly bool $fineGrainedCache
    ) {
    }

    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ThemeConfigChangedEvent::class => 'invalidate',
            ThemeAssignedEvent::class => 'assigned',
            ThemeConfigResetEvent::class => 'reset',
        ];
    }

    public function invalidate(ThemeConfigChangedEvent $event): void
    {
        $tags = [CachedResolvedConfigLoader::buildName($event->getThemeId())];

        if (Feature::isActive('cache_rework')) {
            $this->cacheInvalidator->invalidate($tags);

            return;
        }

        if (!$this->fineGrainedCache) {
            $this->cacheInvalidator->invalidate($tags);

            return;
        }

        $keys = array_keys($event->getConfig());

        foreach ($keys as $key) {
            $tags[] = ThemeConfigValueAccessor::buildName($key);
        }

        $this->cacheInvalidator->invalidate($tags);
    }

    public function assigned(ThemeAssignedEvent $event): void
    {
        $channelId = $event->getChannelId();

        if (Feature::isActive('cache_rework')) {
            $this->cacheInvalidator->invalidate([
                CachedResolvedConfigLoader::buildName($event->getThemeId()),
                CachedDomainLoader::CACHE_KEY,
                Translator::tag($channelId),
            ]);

            return;
        }

        $this->cacheInvalidator->invalidate([
            CachedResolvedConfigLoader::buildName($event->getThemeId()),
            CachedDomainLoader::CACHE_KEY,
            'translation.catalog.' . $channelId,
        ]);
    }

    public function reset(ThemeConfigResetEvent $event): void
    {
        $this->cacheInvalidator->invalidate([CachedResolvedConfigLoader::buildName($event->getThemeId())]);
    }
}
