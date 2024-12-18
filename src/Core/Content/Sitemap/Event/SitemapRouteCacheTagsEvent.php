<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\Event;

use Cicada\Core\Framework\Adapter\Cache\FrontendApiRouteCacheTagsEvent;
use Cicada\Core\Framework\Log\Package;

#[Package('services-settings')]
class SitemapRouteCacheTagsEvent extends FrontendApiRouteCacheTagsEvent
{
}
