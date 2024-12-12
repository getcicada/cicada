<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category\Channel;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * This route can be used to load the navigation of the authenticated sales channel.
 * With the dept can you control how many levels should be loaded.
 * It is also possible to use following aliases as id: "main-navigation", "footer-navigation" and "service-navigation".
 * With this route it is also possible to send the standard API parameters such as: \'page\', \'limit\', \'filter\', etc.
 */
#[Package('content')]
abstract class AbstractNavigationRoute
{
    abstract public function getDecorated(): AbstractNavigationRoute;

    abstract public function load(
        string $activeId,
        string $rootId,
        Request $request,
        ChannelContext $context,
        Criteria $criteria
    ): NavigationRouteResponse;
}