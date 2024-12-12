<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\Channel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('services-settings')]
abstract class AbstractSitemapRoute
{
    abstract public function load(Request $request, ChannelContext $context): SitemapRouteResponse;

    abstract public function getDecorated(): AbstractSitemapRoute;
}
