<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Frontend\Theme\Message;

use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Adapter\Cache\CacheInvalidator;
use Cicada\Frontend\Theme\MD5ThemePathBuilder;
use Cicada\Frontend\Theme\Message\DeleteThemeFilesHandler;
use Cicada\Frontend\Theme\Message\DeleteThemeFilesMessage;

/**
 * @internal
 */
#[CoversClass(DeleteThemeFilesHandler::class)]
class DeleteThemeFilesHandlerTest extends TestCase
{
    public function testFilesAreDeletedIfPathIsCurrentlyNotActive(): void
    {
        $currentPath = 'path';

        $message = new DeleteThemeFilesMessage($currentPath, 'salesChannel', 'theme');

        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects(static::once())->method('deleteDirectory')->with('theme' . \DIRECTORY_SEPARATOR . $currentPath);

        $cacheInvalidator = $this->createMock(CacheInvalidator::class);
        $cacheInvalidator->expects(static::once())->method('invalidate')->with(['theme_scripts_' . $currentPath]);

        $handler = new DeleteThemeFilesHandler(
            $filesystem,
            // the path builder will generate a different path then the hard coded one
            new MD5ThemePathBuilder(),
            $cacheInvalidator
        );

        $handler($message);
    }

    public function testFilesAreNotDeletedIfPathIsCurrentlyActive(): void
    {
        $pathBuilder = new MD5ThemePathBuilder();

        $currentPath = $pathBuilder->assemblePath('salesChannel', 'theme');

        $message = new DeleteThemeFilesMessage($currentPath, 'salesChannel', 'theme');

        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects(static::never())->method('deleteDirectory');

        $handler = new DeleteThemeFilesHandler(
            $filesystem,
            $pathBuilder,
            $this->createMock(CacheInvalidator::class)
        );

        $handler($message);
    }
}
