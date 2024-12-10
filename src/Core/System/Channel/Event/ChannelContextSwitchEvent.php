<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\NestedEvent;
use Cicada\Core\Framework\Event\CicadaChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\DataBag;
use Cicada\Core\System\Channel\ChannelContext;

#[Package('core')]
class ChannelContextSwitchEvent extends NestedEvent implements CicadaChannelEvent
{
    public function __construct(
        private readonly ChannelContext $channelContext,
        private readonly DataBag $requestDataBag
    ) {
    }

    public function getContext(): Context
    {
        return $this->channelContext->getContext();
    }

    public function getRequestDataBag(): DataBag
    {
        return $this->requestDataBag;
    }

    public function getChannelContext(): ChannelContext
    {
        return $this->channelContext;
    }
}
