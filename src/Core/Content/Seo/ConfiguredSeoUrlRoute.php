<?php

declare(strict_types=1);

namespace Cicada\Core\Content\Seo;

use Cicada\Core\Content\Seo\SeoUrlRoute\SeoUrlMapping;
use Cicada\Core\Content\Seo\SeoUrlRoute\SeoUrlRouteConfig;
use Cicada\Core\Content\Seo\SeoUrlRoute\SeoUrlRouteInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelEntity;

#[Package('content')]
class ConfiguredSeoUrlRoute implements SeoUrlRouteInterface
{
    public function __construct(
        private readonly SeoUrlRouteInterface $decorated,
        private readonly SeoUrlRouteConfig $config
    ) {
    }

    public function getConfig(): SeoUrlRouteConfig
    {
        return $this->config;
    }

    public function prepareCriteria(Criteria $criteria, ChannelEntity $channel): void
    {
        $this->decorated->prepareCriteria($criteria, $channel);
    }

    public function getMapping(Entity $entity, ?ChannelEntity $channel): SeoUrlMapping
    {
        return $this->decorated->getMapping($entity, $channel);
    }
}
