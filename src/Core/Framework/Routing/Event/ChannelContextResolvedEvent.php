<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Routing\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('core')]
class ChannelContextResolvedEvent extends Event implements CicadaChannelEvent
{
    public function __construct(
        private readonly ChannelContext $channelContext,
        private readonly string $usedToken
    ) {
    }

    public function getChannelContext(): ChannelContext
    {
        return $this->channelContext;
    }

    public function getContext(): Context
    {
        return $this->channelContext->getContext();
    }

    public function getUsedToken(): string
    {
        return $this->usedToken;
    }
}
