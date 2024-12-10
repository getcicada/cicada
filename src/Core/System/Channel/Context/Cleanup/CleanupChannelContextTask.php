<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Context\Cleanup;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

#[Package('frontend')]
class CleanupChannelContextTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'channel_context.cleanup';
    }

    public static function getDefaultInterval(): int
    {
        return self::DAILY;
    }
}
