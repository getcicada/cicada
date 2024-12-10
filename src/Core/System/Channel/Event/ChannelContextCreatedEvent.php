<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('core')]
class ChannelContextCreatedEvent extends Event implements CicadaChannelEvent
{
    /**
     * @param array<string, mixed> $session
     */
    public function __construct(
        private readonly ChannelContext $channelContext,
        private readonly string $usedToken,
        private readonly array $session = []
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

    /**
     * @return array<string, mixed>
     */
    public function getSession(): array
    {
        return $this->session;
    }
}
