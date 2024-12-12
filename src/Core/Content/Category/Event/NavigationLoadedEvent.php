<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category\Event;

use Cicada\Core\Content\Category\Tree\Tree;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\NestedEvent;
use Cicada\Core\Framework\Event\CicadaChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;

#[Package('content')]
class NavigationLoadedEvent extends NestedEvent implements CicadaChannelEvent
{
    /**
     * @var Tree
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $navigation;

    /**
     * @var ChannelContext
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $channelContext;

    public function __construct(
        Tree $navigation,
        ChannelContext $channelContext
    ) {
        $this->navigation = $navigation;
        $this->channelContext = $channelContext;
    }

    public function getContext(): Context
    {
        return $this->channelContext->getContext();
    }

    public function getNavigation(): Tree
    {
        return $this->navigation;
    }

    public function getChannelContext(): ChannelContext
    {
        return $this->channelContext;
    }
}
