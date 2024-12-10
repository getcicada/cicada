<?php declare(strict_types=1);

namespace Cicada\Frontend\Page\Suggest;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Frontend\Page\PageLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

#[Package('services-settings')]
class SuggestPageLoadedEvent extends PageLoadedEvent
{
    /**
     * @var SuggestPage
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $page;

    public function __construct(
        SuggestPage $page,
        ChannelContext $channelContext,
        Request $request
    ) {
        $this->page = $page;
        parent::__construct($channelContext, $request);
    }

    public function getPage(): SuggestPage
    {
        return $this->page;
    }
}
