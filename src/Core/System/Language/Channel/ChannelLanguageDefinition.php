<?php declare(strict_types=1);

namespace Cicada\Core\System\Language\Channel;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Language\LanguageDefinition;
use Cicada\Core\System\Channel\Entity\ChannelDefinitionInterface;
use Cicada\Core\System\Channel\ChannelContext;

#[Package('frontend')]
class ChannelLanguageDefinition extends LanguageDefinition implements ChannelDefinitionInterface
{
    public function processCriteria(Criteria $criteria, ChannelContext $context): void
    {
        $criteria->addFilter(new EqualsFilter('language.channels.id', $context->getChannel()->getId()));
    }
}
