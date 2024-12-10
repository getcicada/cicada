<?php declare(strict_types=1);

namespace Cicada\Core\Content\Cms\Channel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * This route can be used to load a single resolved cms page of the authenticated sales channel.
 */
#[Package('frontend')]
abstract class AbstractCmsRoute
{
    abstract public function getDecorated(): AbstractCmsRoute;

    abstract public function load(string $id, Request $request, ChannelContext $context): CmsRouteResponse;
}
