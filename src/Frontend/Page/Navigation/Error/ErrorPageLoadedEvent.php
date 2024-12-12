<?php declare(strict_types=1);

namespace Cicada\Frontend\Page\Navigation\Error;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Frontend\Page\PageLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

#[Package('frontend')]
class ErrorPageLoadedEvent extends PageLoadedEvent
{
    /**
     * @var ErrorPage
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $page;

    public function __construct(
        ErrorPage $page,
        ChannelContext $channelContext,
        Request $request
    ) {
        $this->page = $page;
        parent::__construct($channelContext, $request);
    }

    public function getPage(): ErrorPage
    {
        return $this->page;
    }
}
