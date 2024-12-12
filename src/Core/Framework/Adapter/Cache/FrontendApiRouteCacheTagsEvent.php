<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Adapter\Cache;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Core\System\Channel\FrontendApiResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('core')]
class FrontendApiRouteCacheTagsEvent extends Event
{
    public function __construct(
        protected array $tags,
        protected Request $request,
        private readonly FrontendApiResponse $response,
        protected ChannelContext $context,
        protected ?Criteria $criteria
    ) {
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getContext(): ChannelContext
    {
        return $this->context;
    }

    public function getCriteria(): ?Criteria
    {
        return $this->criteria;
    }

    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }

    public function addTags(array $tags): void
    {
        $this->tags = array_merge($this->tags, $tags);
    }

    public function getChannelId(): string
    {
        return $this->context->getChannelId();
    }

    public function getResponse(): FrontendApiResponse
    {
        return $this->response;
    }
}
