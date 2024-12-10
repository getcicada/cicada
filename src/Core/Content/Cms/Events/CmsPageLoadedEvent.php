<?php declare(strict_types=1);

namespace Cicada\Core\Content\Cms\Events;

use Cicada\Core\Content\Cms\CmsPageCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Event\NestedEvent;
use Cicada\Core\Framework\Event\CicadaChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('frontend')]
class CmsPageLoadedEvent extends NestedEvent implements CicadaChannelEvent
{
    /**
     * @var Request
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $request;

    /**
     * @var CmsPageCollection
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $result;

    /**
     * @var ChannelContext
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $channelContext;

    /**
     * @param CmsPageCollection $result
     */
    public function __construct(
        Request $request,
        EntityCollection $result,
        ChannelContext $channelContext
    ) {
        $this->request = $request;
        $this->result = $result;
        $this->channelContext = $channelContext;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @return CmsPageCollection
     */
    public function getResult(): EntityCollection
    {
        return $this->result;
    }

    public function getContext(): Context
    {
        return $this->channelContext->getContext();
    }

    public function getChannelContext(): ChannelContext
    {
        return $this->channelContext;
    }
}
