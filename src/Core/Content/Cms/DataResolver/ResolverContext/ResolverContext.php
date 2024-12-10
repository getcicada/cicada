<?php declare(strict_types=1);

namespace Cicada\Core\Content\Cms\DataResolver\ResolverContext;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('frontend')]
class ResolverContext
{
    public function __construct(
        private readonly ChannelContext $context,
        private readonly Request $request
    ) {
    }

    public function getChannelContext(): ChannelContext
    {
        return $this->context;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
