<?php declare(strict_types=1);

namespace Cicada\Frontend\Page\Navigation;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('frontend')]
interface NavigationPageLoaderInterface
{
    public function load(Request $request, ChannelContext $context): NavigationPage;
}
