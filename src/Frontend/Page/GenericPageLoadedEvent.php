<?php declare(strict_types=1);

namespace Cicada\Frontend\Page;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('frontend')]
class GenericPageLoadedEvent extends PageLoadedEvent
{
    /**
     * @var Page
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $page;

    public function __construct(
        Page $page,
        ChannelContext $channelContext,
        Request $request
    ) {
        $this->page = $page;
        parent::__construct($channelContext, $request);
    }

    public function getPage(): Page
    {
        return $this->page;
    }
}
