<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\NestedEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;

#[Package('core')]
class ChannelContextRestoredEvent extends NestedEvent
{
    public function __construct(
        private readonly ChannelContext $restoredContext,
        private readonly ChannelContext $currentContext
    ) {
    }

    public function getRestoredChannelContext(): ChannelContext
    {
        return $this->restoredContext;
    }

    public function getContext(): Context
    {
        return $this->restoredContext->getContext();
    }

    public function getCurrentChannelContext(): ChannelContext
    {
        return $this->currentContext;
    }
}
