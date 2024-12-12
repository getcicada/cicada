<?php declare(strict_types=1);

namespace Cicada\Frontend\Page\Navigation\Error;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('frontend')]
interface ErrorPageLoaderInterface
{
    public function load(string $cmsErrorLayoutId, Request $request, ChannelContext $context): ErrorPage;
}
