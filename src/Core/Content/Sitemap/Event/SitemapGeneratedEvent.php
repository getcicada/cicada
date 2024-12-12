<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('services-settings')]
class SitemapGeneratedEvent extends Event implements CicadaEvent
{
    public function __construct(private readonly ChannelContext $context)
    {
    }

    public function getChannelContext(): ChannelContext
    {
        return $this->context;
    }

    public function getContext(): Context
    {
        return $this->context->getContext();
    }
}
