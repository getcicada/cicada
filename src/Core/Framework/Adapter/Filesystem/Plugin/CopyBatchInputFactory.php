<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Adapter\Filesystem\Plugin;

use Cicada\Core\Framework\Log\Package;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @internal
 *
 * @codeCoverageIgnore Integration tested with \Cicada\Tests\Integration\Core\Framework\Adapter\Filesystem\Plugin\CopyBatchInputFactoryTest
 */
#[Package('core')]
class CopyBatchInputFactory
{
    /**
     * @return array<CopyBatchInput>
     */
    public function fromDirectory(string $directory, string $target): array
    {
        if (!\is_dir($directory)) {
            return [];
        }

        $parentName = basename($directory);

        $files = (new Finder())->files()->in($directory);

        return array_values(array_map(
            fn (SplFileInfo $file) => new CopyBatchInput(
                $file->getRealPath(),
                [Path::join($target, $parentName, $file->getRelativePathname())]
            ),
            iterator_to_array($files)
        ));
    }
}