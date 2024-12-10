<?php declare(strict_types=1);

namespace Cicada\Core\Content\Seo\MainCategory\Channel;

use Cicada\Core\Content\Seo\MainCategory\MainCategoryDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\Entity\ChannelDefinitionInterface;
use Cicada\Core\System\Channel\ChannelContext;

#[Package('content')]
class ChannelMainCategoryDefinition extends MainCategoryDefinition implements ChannelDefinitionInterface
{
    public function processCriteria(Criteria $criteria, ChannelContext $context): void
    {
        $criteria->addFilter(new EqualsFilter('channelId', $context->getChannel()->getId()));
    }
}
