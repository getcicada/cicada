<?php declare(strict_types=1);

namespace Cicada\Core\System\Snippet\Files;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Cicada\Core\Framework\Bundle;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;

#[Package('services-settings')]
class SnippetFileLoader implements SnippetFileLoaderInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly Connection $connection,
    ) {
    }

    public function loadSnippetFilesIntoCollection(SnippetFileCollection $snippetFileCollection): void
    {
        $this->loadPluginSnippets($snippetFileCollection);
    }

    private function loadPluginSnippets(SnippetFileCollection $snippetFileCollection): void
    {
        try {
            /** @var array<string, string> $authors */
            $authors = $this->connection->fetchAllKeyValue('
                SELECT `base_class` AS `baseClass`, `author`
                FROM `plugin`
            ');
        } catch (Exception) {
            // to get it working in setup without a database connection
            $authors = [];
        }

        foreach ($this->kernel->getBundles() as $bundle) {
            if (!$bundle instanceof Bundle) {
                continue;
            }

            $snippetDir = $bundle->getPath() . '/Resources/snippet';

            if (!is_dir($snippetDir)) {
                continue;
            }

            foreach ($this->loadSnippetFilesInDir($snippetDir, $bundle, $authors) as $snippetFile) {
                if ($snippetFileCollection->hasFileForPath($snippetFile->getPath())) {
                    continue;
                }

                $snippetFileCollection->add($snippetFile);
            }
        }
    }

    /**
     * @param array<string, string> $authors
     *
     * @return AbstractSnippetFile[]
     */
    private function loadSnippetFilesInDir(string $snippetDir, Bundle $bundle, array $authors): array
    {
        $finder = new Finder();
        $finder->in($snippetDir)
            ->files()
            ->name('*.json');

        $snippetFiles = [];

        foreach ($finder->getIterator() as $fileInfo) {
            $nameParts = explode('.', $fileInfo->getFilenameWithoutExtension());

            $snippetFile = null;
            switch (\count($nameParts)) {
                case 2:
                    $snippetFile = new GenericSnippetFile(
                        implode('.', $nameParts),
                        $fileInfo->getPathname(),
                        $nameParts[1],
                        $this->getAuthorFromBundle($bundle, $authors),
                        false,
                        $bundle->getName()
                    );

                    break;
                case 3:
                    $snippetFile = new GenericSnippetFile(
                        implode('.', [$nameParts[0], $nameParts[1]]),
                        $fileInfo->getPathname(),
                        $nameParts[1],
                        $this->getAuthorFromBundle($bundle, $authors),
                        $nameParts[2] === 'base',
                        $bundle->getName()
                    );

                    break;
            }

            if ($snippetFile) {
                $snippetFiles[] = $snippetFile;
            }
        }

        return $snippetFiles;
    }

    /**
     * @param array<string, string> $authors
     */
    private function getAuthorFromBundle(Bundle $bundle, array $authors): string
    {
        if (!$bundle instanceof Plugin) {
            return 'Cicada';
        }

        return $authors[$bundle::class] ?? '';
    }
}
