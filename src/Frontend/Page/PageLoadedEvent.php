<?php declare(strict_types=1);

namespace Cicada\Frontend\Page;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\NestedEvent;
use Cicada\Core\Framework\Event\CicadaChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('frontend')]
abstract class PageLoadedEvent extends NestedEvent implements CicadaChannelEvent
{
    /**
     * @var ChannelContext
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $channelContext;

    /**
     * @var Request
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $request;

    public function __construct(
        ChannelContext $channelContext,
        Request $request
    ) {
        $this->channelContext = $channelContext;
        $this->request = $request;
    }

    /**
     * @return Page|Struct
     */
    abstract public function getPage();

    public function getChannelContext(): ChannelContext
    {
        return $this->channelContext;
    }

    public function getContext(): Context
    {
        return $this->channelContext->getContext();
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
