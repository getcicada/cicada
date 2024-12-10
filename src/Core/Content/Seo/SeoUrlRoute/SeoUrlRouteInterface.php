<?php declare(strict_types=1);

namespace Cicada\Core\Content\Seo\SeoUrlRoute;

use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelEntity;

#[Package('content')]
interface SeoUrlRouteInterface
{
    public function getConfig(): SeoUrlRouteConfig;

    public function prepareCriteria(Criteria $criteria, ChannelEntity $channel): void;

    public function getMapping(Entity $entity, ?ChannelEntity $channel): SeoUrlMapping;
}
