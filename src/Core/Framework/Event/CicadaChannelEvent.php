<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Event;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;

#[Package('core')]
interface CicadaChannelEvent extends CicadaEvent
{
    public function getChannelContext(): ChannelContext;
}
