<?php declare(strict_types=1);

namespace Cicada\Core\Content\LandingPage\Event;

use Cicada\Core\Framework\Adapter\Cache\StoreApiRouteCacheTagsEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Core\System\Channel\StoreApiResponse;
use Symfony\Component\HttpFoundation\Request;

#[Package('frontend')]
class LandingPageRouteCacheTagsEvent extends StoreApiRouteCacheTagsEvent
{
    public function __construct(
        protected string $landingPageId,
        array $tags,
        Request $request,
        StoreApiResponse $response,
        ChannelContext $context,
        ?Criteria $criteria
    ) {
        parent::__construct($tags, $request, $response, $context, $criteria);
    }

    public function getLandingPageId(): string
    {
        return $this->landingPageId;
    }
}
