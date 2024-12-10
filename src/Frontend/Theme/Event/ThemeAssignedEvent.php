<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme\Event;

use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('frontend')]
class ThemeAssignedEvent extends Event
{
    public function __construct(
        private readonly string $themeId,
        private readonly string $channelId
    ) {
    }

    public function getThemeId(): string
    {
        return $this->themeId;
    }

    public function getChannelId(): string
    {
        return $this->channelId;
    }
}
