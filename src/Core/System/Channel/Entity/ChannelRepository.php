<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Entity;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityAggregationResultLoadedEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEventFactory;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntitySearchResultLoadedEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Cicada\Core\Framework\DataAbstractionLayer\Field\AssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Read\EntityReaderInterface;
use Cicada\Core\Framework\DataAbstractionLayer\RepositorySearchDetector;
use Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntityAggregatorInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearcherInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\ArrayEntity;
use Cicada\Core\Profiling\Profiler;
use Cicada\Core\System\Channel\Event\ChannelProcessCriteriaEvent;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @final
 *
 * @template TEntityCollection of EntityCollection
 */
#[Package('frontend')]
class ChannelRepository
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityDefinition $definition,
        private readonly EntityReaderInterface $reader,
        private readonly EntitySearcherInterface $searcher,
        private readonly EntityAggregatorInterface $aggregator,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EntityLoadedEventFactory $eventFactory
    ) {
    }

    /**
     * @throws InconsistentCriteriaIdsException
     *
     * @return EntitySearchResult<TEntityCollection>
     */
    public function search(Criteria $criteria, ChannelContext $channelContext): EntitySearchResult
    {
        if (!$criteria->getTitle()) {
            return $this->_search($criteria, $channelContext);
        }

        return Profiler::trace($criteria->getTitle(), fn () => $this->_search($criteria, $channelContext), 'saleschannel-repository');
    }

    public function aggregate(Criteria $criteria, ChannelContext $channelContext): AggregationResultCollection
    {
        if (!$criteria->getTitle()) {
            return $this->_aggregate($criteria, $channelContext);
        }

        return Profiler::trace($criteria->getTitle(), fn () => $this->_aggregate($criteria, $channelContext), 'saleschannel-repository');
    }

    public function searchIds(Criteria $criteria, ChannelContext $channelContext): IdSearchResult
    {
        if (!$criteria->getTitle()) {
            return $this->_searchIds($criteria, $channelContext);
        }

        return Profiler::trace($criteria->getTitle(), fn () => $this->_searchIds($criteria, $channelContext), 'saleschannel-repository');
    }

    /**
     * @throws InconsistentCriteriaIdsException
     *
     * @return EntitySearchResult<TEntityCollection>
     */
    private function _search(Criteria $criteria, ChannelContext $channelContext): EntitySearchResult
    {
        $criteria = clone $criteria;

        $this->processCriteria($criteria, $channelContext);

        $aggregations = null;
        if ($criteria->getAggregations()) {
            $aggregations = $this->aggregate($criteria, $channelContext);
        }
        if (!RepositorySearchDetector::isSearchRequired($this->definition, $criteria)) {
            $entities = $this->read($criteria, $channelContext);

            return new EntitySearchResult($this->definition->getEntityName(), $entities->count(), $entities, $aggregations, $criteria, $channelContext->getContext());
        }

        $ids = $this->doSearch($criteria, $channelContext);

        if (empty($ids->getIds())) {
            /** @var TEntityCollection $collection */
            $collection = $this->definition->getCollectionClass();

            return new EntitySearchResult($this->definition->getEntityName(), $ids->getTotal(), new $collection(), $aggregations, $criteria, $channelContext->getContext());
        }

        $readCriteria = $criteria->cloneForRead($ids->getIds());

        $entities = $this->read($readCriteria, $channelContext);

        $search = $ids->getData();

        foreach ($entities as $element) {
            if (!\array_key_exists($element->getUniqueIdentifier(), $search)) {
                continue;
            }

            $data = $search[$element->getUniqueIdentifier()];
            unset($data['id']);

            if (empty($data)) {
                continue;
            }

            $element->addExtension('search', new ArrayEntity($data));
        }

        $result = new EntitySearchResult($this->definition->getEntityName(), $ids->getTotal(), $entities, $aggregations, $criteria, $channelContext->getContext());
        $result->addState(...$ids->getStates());

        $event = new EntitySearchResultLoadedEvent($this->definition, $result);
        $this->eventDispatcher->dispatch($event, $event->getName());

        $event = new ChannelEntitySearchResultLoadedEvent($this->definition, $result, $channelContext);
        $this->eventDispatcher->dispatch($event, $event->getName());

        return $result;
    }

    private function _aggregate(Criteria $criteria, ChannelContext $channelContext): AggregationResultCollection
    {
        $criteria = clone $criteria;

        $this->processCriteria($criteria, $channelContext);

        $result = $this->aggregator->aggregate($this->definition, $criteria, $channelContext->getContext());

        $event = new EntityAggregationResultLoadedEvent($this->definition, $result, $channelContext->getContext());
        $this->eventDispatcher->dispatch($event, $event->getName());

        return $result;
    }

    private function _searchIds(Criteria $criteria, ChannelContext $channelContext): IdSearchResult
    {
        $criteria = clone $criteria;

        $this->processCriteria($criteria, $channelContext);

        return $this->doSearch($criteria, $channelContext);
    }

    /**
     * @return TEntityCollection
     */
    private function read(Criteria $criteria, ChannelContext $channelContext): EntityCollection
    {
        $criteria = clone $criteria;

        /** @var TEntityCollection $entities */
        $entities = $this->reader->read($this->definition, $criteria, $channelContext->getContext());

        if ($criteria->getFields() === []) {
            $events = $this->eventFactory->createForChannel($entities->getElements(), $channelContext);
        } else {
            $events = $this->eventFactory->createPartialForChannel($entities->getElements(), $channelContext);
        }

        foreach ($events as $event) {
            $this->eventDispatcher->dispatch($event);
        }

        return $entities;
    }

    private function doSearch(Criteria $criteria, ChannelContext $channelContext): IdSearchResult
    {
        $result = $this->searcher->search($this->definition, $criteria, $channelContext->getContext());

        $event = new ChannelEntityIdSearchResultLoadedEvent($this->definition, $result, $channelContext);
        $this->eventDispatcher->dispatch($event, $event->getName());

        return $result;
    }

    private function processCriteria(Criteria $topCriteria, ChannelContext $channelContext): void
    {
        if (!$this->definition instanceof ChannelDefinitionInterface) {
            return;
        }

        $queue = [
            ['definition' => $this->definition, 'criteria' => $topCriteria, 'path' => ''],
        ];

        $maxCount = 100;

        $processed = [];

        // process all associations breadth-first
        while (!empty($queue) && --$maxCount > 0) {
            $cur = array_shift($queue);

            $definition = $cur['definition'];
            $criteria = $cur['criteria'];
            $path = $cur['path'];
            $processedKey = $path . $definition::class;

            if (isset($processed[$processedKey])) {
                continue;
            }

            if ($definition instanceof ChannelDefinitionInterface) {
                $definition->processCriteria($criteria, $channelContext);

                $eventName = \sprintf('channel.%s.process.criteria', $definition->getEntityName());
                $event = new ChannelProcessCriteriaEvent($criteria, $channelContext);

                $this->eventDispatcher->dispatch($event, $eventName);
            }

            $processed[$processedKey] = true;

            foreach ($criteria->getAssociations() as $associationName => $associationCriteria) {
                // find definition
                $field = $definition->getField($associationName);
                if (!$field instanceof AssociationField) {
                    continue;
                }

                $referenceDefinition = $field->getReferenceDefinition();
                $queue[] = ['definition' => $referenceDefinition, 'criteria' => $associationCriteria, 'path' => $path . '.' . $associationName];

                if (!$field instanceof ManyToManyAssociationField) {
                    continue;
                }

                $referenceDefinition = $field->getToManyReferenceDefinition();
                $queue[] = ['definition' => $referenceDefinition, 'criteria' => $associationCriteria, 'path' => $path . '.' . $associationName];
            }
        }
    }
}
