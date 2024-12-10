<?php declare(strict_types=1);

namespace Cicada\Core\Content\Seo\Channel;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * This route can be used to load all seo urls of the authenticated sales channel.
 * With this route it is also possible to send the standard API parameters such as: 'page', 'limit', 'filter', etc.
 */
#[Package('content')]
abstract class AbstractSeoUrlRoute
{
    abstract public function getDecorated(): AbstractSeoUrlRoute;

    abstract public function load(Request $request, ChannelContext $context, Criteria $criteria): SeoUrlRouteResponse;
}
