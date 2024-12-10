<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme\Message;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\AsyncMessageInterface;

/**
 * used to delay the deletion of theme files
 */
#[Package('frontend')]
class DeleteThemeFilesMessage implements AsyncMessageInterface
{
    public function __construct(
        private readonly string $themePath,
        private readonly string $channelId,
        private readonly string $themeId
    ) {
    }

    public function getThemePath(): string
    {
        return $this->themePath;
    }

    public function getChannelId(): string
    {
        return $this->channelId;
    }

    public function getThemeId(): string
    {
        return $this->themeId;
    }
}
