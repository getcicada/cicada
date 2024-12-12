<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category\Channel;

use Cicada\Core\Content\Category\CategoryCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\StoreApiResponse;

#[Package('content')]
class CategoryListRouteResponse extends StoreApiResponse
{
    /**
     * @var EntitySearchResult<CategoryCollection>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    /**
     * @param EntitySearchResult<CategoryCollection> $object
     */
    public function __construct(EntitySearchResult $object)
    {
        parent::__construct($object);
    }

    public function getCategories(): CategoryCollection
    {
        return $this->object->getEntities();
    }
}
