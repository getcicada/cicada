<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Routing;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\AbstractRouteScope;
use Cicada\Core\Framework\Routing\ChannelContextRouteScopeDependant;
use Cicada\Core\ChannelRequest;
use Symfony\Component\HttpFoundation\Request;

#[Package('frontend')]
class FrontendRouteScope extends AbstractRouteScope implements ChannelContextRouteScopeDependant
{
    final public const ID = 'frontend';

    /**
     * @var array<string>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $allowedPaths = [];

    public function isAllowed(Request $request): bool
    {
        return $request->attributes->has(ChannelRequest::ATTRIBUTE_IS_SALES_CHANNEL_REQUEST)
            && $request->attributes->get(ChannelRequest::ATTRIBUTE_IS_SALES_CHANNEL_REQUEST) === true
        ;
    }

    public function getId(): string
    {
        return self::ID;
    }
}
