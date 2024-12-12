<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category\Channel;

use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\Entity\ChannelDefinitionInterface;
use Cicada\Core\System\Channel\ChannelContext;

#[Package('content')]
class ChannelCategoryDefinition extends CategoryDefinition implements ChannelDefinitionInterface
{
    public function processCriteria(Criteria $criteria, ChannelContext $context): void
    {
    }
}
