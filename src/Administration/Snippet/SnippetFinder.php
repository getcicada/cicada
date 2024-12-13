<?php declare(strict_types=1);

namespace Cicada\Administration\Snippet;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Kernel;
use Symfony\Component\Finder\Finder;

/**
 * @internal
 */
#[Package('administration')]
class SnippetFinder implements SnippetFinderInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly Kernel $kernel
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function findSnippets(string $locale): array
    {
        $snippetFiles = $this->findSnippetFiles($locale);
        $snippets = $this->parseFiles($snippetFiles);

        $snippets = [...$snippets];

        if (!\count($snippets)) {
            return [];
        }

        return $snippets;
    }

    /**
     * @return array<int, string>
     */
    private function getBundlePaths(): array
    {
        $plugins = $this->kernel->getPluginLoader()->getPluginInstances()->all();
        $activePlugins = $this->kernel->getPluginLoader()->getPluginInstances()->getActives();
        $bundles = $this->kernel->getBundles();
        $paths = [];

        foreach ($activePlugins as $plugin) {
            $pluginPath = $plugin->getPath() . '/Resources/app/administration/src';
            if (!file_exists($pluginPath)) {
                continue;
            }

            $paths[] = $pluginPath;
        }

        foreach ($bundles as $bundle) {
            if (\in_array($bundle, $plugins, true)) {
                continue;
            }

            if ($bundle->getName() === 'Administration') {
                $paths = array_merge($paths, [
                    $bundle->getPath() . '/Resources/app/administration/src/app/snippet',
                    $bundle->getPath() . '/Resources/app/administration/src/module/*/snippet',
                    $bundle->getPath() . '/Resources/app/administration/src/app/component/*/*/snippet',
                ]);

                continue;
            }

            if ($bundle->getName() === 'Storefront') {
                $paths = array_merge($paths, [
                    $bundle->getPath() . '/Resources/app/administration/src/app/snippet',
                    $bundle->getPath() . '/Resources/app/administration/src/modules/*/snippet',
                ]);

                continue;
            }

            $bundlePath = $bundle->getPath() . '/Resources/app/administration/src';

            if (!file_exists($bundlePath)) {
                continue;
            }

            $paths[] = $bundlePath;
        }

        return $paths;
    }

    /**
     * @return array<int, string>
     */
    private function findSnippetFiles(string $locale): array
    {
        $finder = (new Finder())
            ->files()
            ->exclude('node_modules')
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
            ->ignoreUnreadableDirs()
            ->name(\sprintf('%s.json', $locale))
            ->in($this->getBundlePaths());

        $iterator = $finder->getIterator();
        $files = [];

        foreach ($iterator as $file) {
            $files[] = $file->getRealPath();
        }

        return \array_unique($files);
    }

    /**
     * @param array<int, string> $files
     *
     * @return array<string, mixed>
     */
    private function parseFiles(array $files): array
    {
        $snippets = [[]];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content !== false) {
                $snippets[] = json_decode($content, true, 512, \JSON_THROW_ON_ERROR) ?? [];
            }
        }

        $snippets = array_replace_recursive(...$snippets);
        ksort($snippets);

        return $snippets;
    }
}
