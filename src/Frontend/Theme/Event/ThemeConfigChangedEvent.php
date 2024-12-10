<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme\Event;

use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('frontend')]
class ThemeConfigChangedEvent extends Event
{
    public function __construct(
        private readonly string $themeId,
        protected array $config
    ) {
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getThemeId(): string
    {
        return $this->themeId;
    }
}
