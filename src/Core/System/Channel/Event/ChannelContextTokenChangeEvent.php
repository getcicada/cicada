<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('core')]
class ChannelContextTokenChangeEvent extends Event implements CicadaChannelEvent
{
    /**
     * @var ChannelContext
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $channelContext;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $previousToken;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $currentToken;

    public function __construct(
        ChannelContext $channelContext,
        string $previousToken,
        string $currentToken
    ) {
        $this->channelContext = $channelContext;
        $this->previousToken = $previousToken;
        $this->currentToken = $currentToken;
    }

    public function getChannelContext(): ChannelContext
    {
        return $this->channelContext;
    }

    public function getContext(): Context
    {
        return $this->channelContext->getContext();
    }

    public function getPreviousToken(): string
    {
        return $this->previousToken;
    }

    public function getCurrentToken(): string
    {
        return $this->currentToken;
    }
}
