<?php declare(strict_types=1);

namespace Cicada\Core\Content\Seo\SeoUrl\Channel;

use Cicada\Core\Content\Seo\SeoUrl\SeoUrlDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\Entity\ChannelDefinitionInterface;
use Cicada\Core\System\Channel\ChannelContext;

#[Package('content')]
class ChannelSeoUrlDefinition extends SeoUrlDefinition implements ChannelDefinitionInterface
{
    public function processCriteria(Criteria $criteria, ChannelContext $context): void
    {
        $criteria->addFilter(new EqualsFilter('languageId', $context->getContext()->getLanguageId()));
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
            new EqualsFilter('channelId', $context->getChannel()->getId()),
            new EqualsFilter('channelId', null),
        ]));
        $criteria->addFilter(new EqualsFilter('isCanonical', true));
        $criteria->addFilter(new EqualsFilter('isDeleted', false));
    }
}
