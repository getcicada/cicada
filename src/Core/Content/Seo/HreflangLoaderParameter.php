<?php declare(strict_types=1);

namespace Cicada\Core\Content\Seo;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;

#[Package('content')]
class HreflangLoaderParameter
{
    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $route;

    /**
     * @var array
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $routeParameters;

    /**
     * @var ChannelContext
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $channelContext;

    public function __construct(
        string $route,
        array $routeParameters,
        ChannelContext $channelContext
    ) {
        $this->route = $route;
        $this->routeParameters = $routeParameters;
        $this->channelContext = $channelContext;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getRouteParameters(): array
    {
        return $this->routeParameters;
    }

    public function getChannelContext(): ChannelContext
    {
        return $this->channelContext;
    }
}
