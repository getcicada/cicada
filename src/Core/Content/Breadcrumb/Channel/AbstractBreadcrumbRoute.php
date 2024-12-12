<?php declare(strict_types=1);

namespace Cicada\Core\Content\Breadcrumb\Channel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * @experimental stableVersion:v6.7.0 feature:BREADCRUMB_STORE_API
 */
#[Package('content')]
abstract class AbstractBreadcrumbRoute
{
    abstract public function getDecorated(): AbstractBreadcrumbRoute;

    abstract public function load(Request $request, ChannelContext $channelContext): BreadcrumbRouteResponse;
}
