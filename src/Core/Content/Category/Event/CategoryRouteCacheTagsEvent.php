<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category\Event;

use Cicada\Core\Framework\Adapter\Cache\StoreApiRouteCacheTagsEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Core\System\Channel\StoreApiResponse;
use Symfony\Component\HttpFoundation\Request;

#[Package('content')]
class CategoryRouteCacheTagsEvent extends StoreApiRouteCacheTagsEvent
{
    /**
     * @param array<string> $tags
     */
    public function __construct(
        protected string $navigationId,
        array $tags,
        Request $request,
        StoreApiResponse $response,
        ChannelContext $context,
        ?Criteria $criteria
    ) {
        parent::__construct($tags, $request, $response, $context, $criteria);
    }

    public function getNavigationId(): string
    {
        return $this->navigationId;
    }
}
