<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Context;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\BaseContext;

/**
 * @internal
 */
#[Package('core')]
abstract class AbstractBaseContextFactory
{
    /**
     * @param array<string, mixed> $options
     */
    abstract public function create(string $channelId, array $options = []): BaseContext;
}
