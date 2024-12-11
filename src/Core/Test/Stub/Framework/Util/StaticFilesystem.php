<?php declare(strict_types=1);

namespace Cicada\Core\Test\Stub\Framework\Util;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Util\Filesystem;
use Cicada\Core\Framework\Util\UtilException;
use Symfony\Component\Filesystem\Path;

/**
 * @internal
 */
#[Package('core')]
class StaticFilesystem extends Filesystem
{
    /**
     * @param array<string, string> $files
     */
    public function __construct(private readonly array $files = [])
    {
        parent::__construct('/app-root');
    }

    public function has(string ...$path): bool
    {
        return isset($this->files[Path::join(...$path)]);
    }

    public function read(string ...$path): string
    {
        if (!$this->has(...$path)) {
            throw UtilException::cannotFindFileInFilesystem(Path::join(...$path), $this->location);
        }

        return $this->files[Path::join(...$path)];
    }

    /**
     * {@inheritDoc}
     */
    public function findFiles(string $name, string $in): array
    {
        // not supported
        return [];
    }
}