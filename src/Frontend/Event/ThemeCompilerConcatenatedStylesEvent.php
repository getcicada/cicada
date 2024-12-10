<?php declare(strict_types=1);

namespace Cicada\Frontend\Event;

use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('frontend')]
class ThemeCompilerConcatenatedStylesEvent extends Event
{
    public function __construct(
        private string $concatenatedStyles,
        private readonly string $channelId
    ) {
    }

    public function getConcatenatedStyles(): string
    {
        return $this->concatenatedStyles;
    }

    public function setConcatenatedStyles(string $concatenatedStyles): void
    {
        $this->concatenatedStyles = $concatenatedStyles;
    }

    public function getChannelId(): string
    {
        return $this->channelId;
    }
}
