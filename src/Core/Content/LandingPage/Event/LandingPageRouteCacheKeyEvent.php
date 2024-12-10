<?php declare(strict_types=1);

namespace Cicada\Core\Content\LandingPage\Event;

use Cicada\Core\Framework\Adapter\Cache\StoreApiRouteCacheKeyEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('frontend')]
class LandingPageRouteCacheKeyEvent extends StoreApiRouteCacheKeyEvent
{
    public function __construct(
        protected string $landingPageId,
        array $parts,
        Request $request,
        ChannelContext $context,
        ?Criteria $criteria
    ) {
        parent::__construct($parts, $request, $context, $criteria);
    }

    public function getLandingPageId(): string
    {
        return $this->landingPageId;
    }
}
