<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Context;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;

#[Package('core')]
abstract class AbstractChannelContextFactory
{
    abstract public function getDecorated(): AbstractChannelContextFactory;

    /**
     * @param array<string, mixed> $options
     */
    abstract public function create(string $token, string $channelId, array $options = []): ChannelContext;
}
