<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme\Message;

use League\Flysystem\FilesystemOperator;
use Cicada\Core\Framework\Adapter\Cache\CacheInvalidator;
use Cicada\Core\Framework\Log\Package;
use Cicada\Frontend\Theme\AbstractThemePathBuilder;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
#[Package('frontend')]
final class DeleteThemeFilesHandler
{
    public function __construct(
        private readonly FilesystemOperator $filesystem,
        private readonly AbstractThemePathBuilder $pathBuilder,
        private readonly CacheInvalidator $cacheInvalidator
    ) {
    }

    public function __invoke(DeleteThemeFilesMessage $message): void
    {
        $currentPath = $this->pathBuilder->assemblePath($message->getChannelId(), $message->getThemeId());

        if ($currentPath === $message->getThemePath()) {
            return;
        }

        $this->filesystem->deleteDirectory('theme' . \DIRECTORY_SEPARATOR . $message->getThemePath());
        $this->cacheInvalidator->invalidate([
            'theme_scripts_' . $message->getThemePath(),
        ]);
    }
}
