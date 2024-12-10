<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Context;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;

/**
 * @internal
 */
#[Package('core')]
interface ChannelContextServiceInterface
{
    public function get(ChannelContextServiceParameters $parameters): ChannelContext;
}
