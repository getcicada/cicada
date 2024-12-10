<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\DataAbstractionLayer;

use Cicada\Core\Framework\DataAbstractionLayer\Dbal\Common\IteratorFactory;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexer;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\ManyToManyIdFieldUpdater;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\System\Channel\Event\ChannelIndexerEvent;
use Cicada\Core\System\Channel\ChannelDefinition;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Package('frontend')]
class ChannelIndexer extends EntityIndexer
{
    final public const MANY_TO_MANY_UPDATER = 'channel.many-to-many';

    /**
     * @internal
     */
    public function __construct(
        private readonly IteratorFactory $iteratorFactory,
        private readonly EntityRepository $repository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ManyToManyIdFieldUpdater $manyToManyUpdater
    ) {
    }

    public function getName(): string
    {
        return 'channel.indexer';
    }

    public function iterate(?array $offset): ?EntityIndexingMessage
    {
        $iterator = $this->iteratorFactory->createIterator($this->repository->getDefinition(), $offset);

        $ids = $iterator->fetch();

        if (empty($ids)) {
            return null;
        }

        return new ChannelIndexingMessage(array_values($ids), $iterator->getOffset());
    }

    public function update(EntityWrittenContainerEvent $event): ?EntityIndexingMessage
    {
        $updates = $event->getPrimaryKeys(ChannelDefinition::ENTITY_NAME);

        if (empty($updates)) {
            return null;
        }

        return new ChannelIndexingMessage(array_values($updates), null, $event->getContext());
    }

    public function handle(EntityIndexingMessage $message): void
    {
        $ids = $message->getData();
        if (!\is_array($ids)) {
            return;
        }

        $ids = array_unique(array_filter($ids));
        if (empty($ids)) {
            return;
        }

        if ($message->allow(self::MANY_TO_MANY_UPDATER)) {
            $this->manyToManyUpdater->update(ChannelDefinition::ENTITY_NAME, $ids, $message->getContext());
        }

        $this->eventDispatcher->dispatch(new ChannelIndexerEvent($ids, $message->getContext(), $message->getSkip()));
    }

    public function getTotal(): int
    {
        return $this->iteratorFactory->createIterator($this->repository->getDefinition())->fetchCount();
    }

    public function getDecorated(): EntityIndexer
    {
        throw new DecorationPatternException(static::class);
    }

    public function getOptions(): array
    {
        return [
            self::MANY_TO_MANY_UPDATER,
        ];
    }
}