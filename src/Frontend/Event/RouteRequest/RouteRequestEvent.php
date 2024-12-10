<?php declare(strict_types=1);

namespace Cicada\Frontend\Event\RouteRequest;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Event\NestedEvent;
use Cicada\Core\Framework\Event\CicadaChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('frontend')]
abstract class RouteRequestEvent extends NestedEvent implements CicadaChannelEvent
{
    private readonly Criteria $criteria;

    public function __construct(
        private readonly Request $frontendRequest,
        private readonly Request $storeApiRequest,
        private readonly ChannelContext $channelContext,
        ?Criteria $criteria = null
    ) {
        $this->criteria = $criteria ?? new Criteria();
    }

    public function getFrontendRequest(): Request
    {
        return $this->frontendRequest;
    }

    public function getStoreApiRequest(): Request
    {
        return $this->storeApiRequest;
    }

    public function getChannelContext(): ChannelContext
    {
        return $this->channelContext;
    }

    public function getContext(): Context
    {
        return $this->channelContext->getContext();
    }

    public function getCriteria(): Criteria
    {
        return $this->criteria;
    }
}
