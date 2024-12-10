<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Routing;

use Cicada\Core\Framework\Adapter\Cache\CacheInvalidator;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelDefinition;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('frontend')]
class CachedDomainLoaderInvalidator implements EventSubscriberInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly CacheInvalidator $logger)
    {
    }

    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            EntityWrittenContainerEvent::class => [
                ['invalidate', 2000],
            ],
        ];
    }

    public function invalidate(EntityWrittenContainerEvent $event): void
    {
        if ($event->getEventByEntityName(ChannelDefinition::ENTITY_NAME)) {
            $this->logger->invalidate([CachedDomainLoader::CACHE_KEY]);
        }
    }
}
