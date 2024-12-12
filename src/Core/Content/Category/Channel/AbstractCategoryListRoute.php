<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category\Channel;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;

#[Package('content')]
abstract class AbstractCategoryListRoute
{
    abstract public function getDecorated(): AbstractCategoryListRoute;

    abstract public function load(Criteria $criteria, ChannelContext $context): CategoryListRouteResponse;
}
