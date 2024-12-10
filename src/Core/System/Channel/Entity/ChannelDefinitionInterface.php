<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Entity;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;

#[Package('frontend')]
interface ChannelDefinitionInterface
{
    /**
     * Called after the api prepared the criteria for the repository.
     * It is possible to remove associations, filters or sortings, throw exception for invalid access
     * or adding some base conditions to filter only active entities or only entities which are relate to the
     * current sales channel id.
     *
     * @example
     *      $criteria->addFilter(new EqualsFilter('product.active', true));
     *      $criteria->addFilter(new EqualsFilter('currency.channel.id', $context->getChannel()->getId())
     */
    public function processCriteria(Criteria $criteria, ChannelContext $context): void;
}
