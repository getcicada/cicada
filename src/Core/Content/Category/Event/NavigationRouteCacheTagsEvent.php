<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category\Event;

use Cicada\Core\Framework\Adapter\Cache\StoreApiRouteCacheTagsEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Core\System\Channel\StoreApiResponse;
use Symfony\Component\HttpFoundation\Request;

#[Package('content')]
class NavigationRouteCacheTagsEvent extends StoreApiRouteCacheTagsEvent
{
    /**
     * @param array<string> $tags
     */
    public function __construct(
        array $tags,
        protected string $active,
        protected string $rootId,
        protected int $depth,
        Request $request,
        StoreApiResponse $response,
        ChannelContext $context,
        Criteria $criteria
    ) {
        parent::__construct($tags, $request, $response, $context, $criteria);
    }

    public function getActive(): string
    {
        return $this->active;
    }

    public function getRootId(): string
    {
        return $this->rootId;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }
}
