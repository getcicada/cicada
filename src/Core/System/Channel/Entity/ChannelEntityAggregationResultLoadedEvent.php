<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Entity;

use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityAggregationResultLoadedEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Cicada\Core\Framework\Event\CicadaChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;

#[Package('frontend')]
class ChannelEntityAggregationResultLoadedEvent extends EntityAggregationResultLoadedEvent implements CicadaChannelEvent
{
    private readonly ChannelContext $channelContext;

    public function __construct(
        EntityDefinition $definition,
        AggregationResultCollection $result,
        ChannelContext $channelContext
    ) {
        parent::__construct($definition, $result, $channelContext->getContext());
        $this->channelContext = $channelContext;
    }

    public function getName(): string
    {
        return 'channel.' . parent::getName();
    }

    public function getChannelContext(): ChannelContext
    {
        return $this->channelContext;
    }
}
