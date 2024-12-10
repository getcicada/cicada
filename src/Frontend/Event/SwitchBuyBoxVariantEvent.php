<?php declare(strict_types=1);

namespace Cicada\Frontend\Event;

use Cicada\Core\Content\Product\Channel\ChannelProductEntity;
use Cicada\Core\Content\Property\PropertyGroupCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('frontend')]
class SwitchBuyBoxVariantEvent extends Event implements CicadaChannelEvent
{
    public function __construct(
        private readonly string $elementId,
        private readonly ChannelProductEntity $product,
        private readonly ?PropertyGroupCollection $configurator,
        private readonly Request $request,
        private readonly ChannelContext $channelContext
    ) {
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getElementId(): string
    {
        return $this->elementId;
    }

    public function getProduct(): ChannelProductEntity
    {
        return $this->product;
    }

    public function getConfigurator(): ?PropertyGroupCollection
    {
        return $this->configurator;
    }

    public function getChannelContext(): ChannelContext
    {
        return $this->channelContext;
    }

    public function getContext(): Context
    {
        return $this->channelContext->getContext();
    }
}
