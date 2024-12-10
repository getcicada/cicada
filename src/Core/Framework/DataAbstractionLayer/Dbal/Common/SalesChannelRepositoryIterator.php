<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Dbal\Common;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\Entity\ChannelRepository;
use Cicada\Core\System\Channel\ChannelContext;

/**
 * @template TEntityCollection of EntityCollection
 */
#[Package('core')]
class ChannelRepositoryIterator
{
    private readonly Criteria $criteria;

    /**
     * @param ChannelRepository<TEntityCollection> $repository
     */
    public function __construct(
        private readonly ChannelRepository $repository,
        private readonly ChannelContext $context,
        ?Criteria $criteria = null
    ) {
        if ($criteria === null) {
            $criteria = new Criteria();
            $criteria->setOffset(0);
            $criteria->setLimit(50);
        }

        $this->criteria = $criteria;
    }

    public function getTotal(): int
    {
        $criteria = clone $this->criteria;
        $criteria->setOffset(0);
        $criteria->setLimit(1);
        $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);

        return $this->repository->searchIds($criteria, $this->context)->getTotal();
    }

    /**
     * @return list<string>|list<array<string, string>>|null
     */
    public function fetchIds(): ?array
    {
        $this->criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_NONE);
        $ids = $this->repository->searchIds($this->criteria, $this->context);
        $this->criteria->setOffset($this->criteria->getOffset() + $this->criteria->getLimit());

        if (!empty($ids->getIds())) {
            return $ids->getIds();
        }

        return null;
    }

    /**
     * @return EntitySearchResult<TEntityCollection>|null
     */
    public function fetch(): ?EntitySearchResult
    {
        $this->criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_NONE);
        $result = $this->repository->search($this->criteria, $this->context);

        // increase offset for next iteration
        $this->criteria->setOffset($this->criteria->getOffset() + $this->criteria->getLimit());

        if (empty($result->getIds())) {
            return null;
        }

        return $result;
    }
}
