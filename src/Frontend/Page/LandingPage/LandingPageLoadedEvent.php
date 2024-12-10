<?php declare(strict_types=1);

namespace Cicada\Frontend\Page\LandingPage;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Frontend\Page\PageLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

#[Package('frontend')]
class LandingPageLoadedEvent extends PageLoadedEvent
{
    /**
     * @var LandingPage
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $page;

    public function __construct(
        LandingPage $page,
        ChannelContext $channelContext,
        Request $request
    ) {
        $this->page = $page;
        parent::__construct($channelContext, $request);
    }

    public function getPage(): LandingPage
    {
        return $this->page;
    }
}
