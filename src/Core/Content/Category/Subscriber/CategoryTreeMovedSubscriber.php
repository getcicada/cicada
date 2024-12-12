<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category\Subscriber;

use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelDefinition;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('content')]
readonly class CategoryTreeMovedSubscriber implements EventSubscriberInterface
{
    /**
     * @internal
     */
    public function __construct(
        private EntityIndexerRegistry $indexerRegistry
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityWrittenContainerEvent::class => 'detectChannelEntryPoints',
        ];
    }

    public function detectChannelEntryPoints(EntityWrittenContainerEvent $event): void
    {
        $properties = ['navigationCategoryId', 'footerCategoryId', 'serviceCategoryId'];

        $channelIds = $event->getPrimaryKeysWithPropertyChange(ChannelDefinition::ENTITY_NAME, $properties);

        if (empty($channelIds)) {
            return;
        }

        $this->indexerRegistry->sendIndexingMessage(['category.indexer', 'product.indexer']);
    }
}
