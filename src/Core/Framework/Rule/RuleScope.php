<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Rule;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;

#[Package('services-settings')]
abstract class RuleScope
{
    abstract public function getContext(): Context;

    abstract public function getChannelContext(): ChannelContext;

    public function getCurrentTime(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
