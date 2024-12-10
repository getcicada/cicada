<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Event\CicadaChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;

#[Package('frontend')]
class ChannelProcessCriteriaEvent implements CicadaChannelEvent
{
    public function __construct(
        private readonly Criteria $criteria,
        private readonly ChannelContext $channelContext
    ) {
    }

    public function getCriteria(): Criteria
    {
        return $this->criteria;
    }

    public function getChannelContext(): ChannelContext
    {
        return $this->channelContext;
    }

    public function getContext(): Context
    {
        return $this->channelContext->getContext();
    }
}
