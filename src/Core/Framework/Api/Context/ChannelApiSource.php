<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\Context;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\JsonSerializableTrait;

#[Package('core')]
class ChannelApiSource implements ContextSource, \JsonSerializable
{
    use JsonSerializableTrait;

    public string $type = 'sales-channel';

    public function __construct(private readonly string $channelId)
    {
    }

    public function getChannelId(): string
    {
        return $this->channelId;
    }
}
