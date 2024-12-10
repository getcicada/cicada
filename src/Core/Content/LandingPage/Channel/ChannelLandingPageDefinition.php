<?php declare(strict_types=1);

namespace Cicada\Core\Content\LandingPage\Channel;

use Cicada\Core\Content\LandingPage\LandingPageDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\Entity\ChannelDefinitionInterface;
use Cicada\Core\System\Channel\ChannelContext;

#[Package('frontend')]
class ChannelLandingPageDefinition extends LandingPageDefinition implements ChannelDefinitionInterface
{
    public function processCriteria(Criteria $criteria, ChannelContext $context): void
    {
    }
}
