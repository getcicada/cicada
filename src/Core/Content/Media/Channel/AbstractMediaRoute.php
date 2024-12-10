<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Channel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('core')]
abstract class AbstractMediaRoute
{
    abstract public function getDecorated(): AbstractMediaRoute;

    abstract public function load(Request $request, ChannelContext $context): MediaRouteResponse;
}
