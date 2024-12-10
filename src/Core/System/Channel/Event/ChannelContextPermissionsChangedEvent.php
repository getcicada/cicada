<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\NestedEvent;
use Cicada\Core\Framework\Event\CicadaChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;

#[Package('core')]
class ChannelContextPermissionsChangedEvent extends NestedEvent implements CicadaChannelEvent
{
    /**
     * @var array
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $permissions = [];

    public function __construct(
        private readonly ChannelContext $channelContext,
        array $permissions
    ) {
        $this->permissions = $permissions;
    }

    public function getContext(): Context
    {
        return $this->channelContext->getContext();
    }

    public function getChannelContext(): ChannelContext
    {
        return $this->channelContext;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }
}
