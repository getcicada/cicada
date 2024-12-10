<?php declare(strict_types=1);

namespace Cicada\Core\Content\LandingPage\Channel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('frontend')]
abstract class AbstractLandingPageRoute
{
    abstract public function getDecorated(): AbstractLandingPageRoute;

    abstract public function load(string $landingPageId, Request $request, ChannelContext $context): LandingPageRouteResponse;
}
