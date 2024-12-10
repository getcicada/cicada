<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme;

use Cicada\Core\Framework\Log\Package;
use Cicada\Frontend\Theme\Exception\ThemeCompileException;
use Cicada\Frontend\Theme\Exception\ThemeException;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\File;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\FileCollection;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\FrontendPluginConfiguration;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\FrontendPluginConfigurationCollection;

#[Package('frontend')]
class ThemeFileResolver
{
    final public const SCRIPT_FILES = 'script';
    final public const STYLE_FILES = 'style';

    /**
     * @internal
     */
    public function __construct(private readonly ThemeFilesystemResolver $themeFilesystemResolver)
    {
    }

    /**
     * @return array<string, FileCollection>
     */
    public function resolveFiles(
        FrontendPluginConfiguration $themeConfig,
        FrontendPluginConfigurationCollection $configurationCollection,
        bool $onlySourceFiles
    ): array {
        return [
            self::SCRIPT_FILES => $this->resolve(
                $themeConfig,
                $configurationCollection,
                $onlySourceFiles,
                $this->resolveScriptFiles(...)
            ),
            self::STYLE_FILES => $this->resolve(
                $themeConfig,
                $configurationCollection,
                $onlySourceFiles,
                fn (FrontendPluginConfiguration $configuration) => $configuration->getStyleFiles()
            ),
        ];
    }

    private function resolveScriptFiles(FrontendPluginConfiguration $configuration, bool $onlySourceFiles): FileCollection
    {
        $fileCollection = new FileCollection();
        $scriptFiles = $configuration->getScriptFiles();
        $addSourceFile = $configuration->getFrontendEntryFilepath() && $onlySourceFiles;

        // add source file at the beginning if no other theme is included first
        if ($addSourceFile
            && $configuration->getFrontendEntryFilepath()
            && ($scriptFiles->count() === 0 || !$scriptFiles->first() || !$this->isInclude($scriptFiles->first()->getFilepath()))
        ) {
            $fileCollection->add(new File($configuration->getFrontendEntryFilepath()));
        }
        foreach ($scriptFiles as $scriptFile) {
            if ($onlySourceFiles && !$this->isInclude($scriptFile->getFilepath())) {
                continue;
            }
            $fileCollection->add($scriptFile);
        }
        if ($addSourceFile
            && $configuration->getFrontendEntryFilepath()
            && $scriptFiles->count() > 0
            && $scriptFiles->first()
            && $this->isInclude($scriptFiles->first()->getFilepath())
        ) {
            $fileCollection->add(new File($configuration->getFrontendEntryFilepath()));
        }

        foreach ($fileCollection as $file) {
            $file->assetName = $configuration->getAssetName();
        }

        return $fileCollection;
    }

    /**
     * @param callable(FrontendPluginConfiguration, bool): FileCollection $configFileResolver
     * @param array<int, string> $included
     */
    private function resolve(
        FrontendPluginConfiguration $themeConfig,
        FrontendPluginConfigurationCollection $configurationCollection,
        bool $onlySourceFiles,
        callable $configFileResolver,
        array $included = []
    ): FileCollection {
        // convertPathsToAbsolute changes the path, this should not affect the passed configuration
        $themeConfig = clone $themeConfig;

        $files = $configFileResolver($themeConfig, $onlySourceFiles);

        if ($files->count() === 0) {
            return $files;
        }

        $this->convertPathsToAbsolute($themeConfig, $files);

        $resolvedFiles = new FileCollection();
        $nextIncluded = $included;
        foreach ($files as $file) {
            $filepath = $file->getFilepath();
            if ($this->isInclude($filepath)) {
                $nextIncluded[] = $filepath;
            }
        }
        foreach ($files as $file) {
            $filepath = $file->getFilepath();
            if (!$this->isInclude($filepath)) {
                if (file_exists($filepath)) {
                    $resolvedFiles->add($file);

                    continue;
                }
                // removes file with old js structure (before async changes) from collection
                if (!str_ends_with($filepath, $file->assetName . '/' . basename($filepath))) {
                    continue;
                }

                throw new ThemeCompileException(
                    $themeConfig->getTechnicalName(),
                    \sprintf('Unable to load file "Resources/%s". Did you forget to build the theme? Try running ./bin/build-frontend.sh', $filepath)
                );
            }

            // bundle or wildcard already included? skip to prevent duplicate style/script injection
            if (\in_array($filepath, $included, true)) {
                continue;
            }
            $included[] = $filepath;
            if ($filepath === '@Plugins') {
                foreach ($configurationCollection->getNoneThemes() as $plugin) {
                    foreach ($this->resolve($plugin, $configurationCollection, $onlySourceFiles, $configFileResolver, $nextIncluded) as $item) {
                        $resolvedFiles->add($item);
                    }
                }

                continue;
            }
            if ($filepath === '@FrontendBootstrap') {
                $resolvedFiles->add(new File(
                    __DIR__ . '/../Resources/app/frontend/src/scss/base.scss',
                    ['vendor' => __DIR__ . '/../Resources/app/frontend/vendor']
                ));

                continue;
            }
            // Resolve @ dependencies
            $name = mb_substr($filepath, 1);
            $configuration = $configurationCollection->getByTechnicalName($name);
            if (!$configuration) {
                throw ThemeException::couldNotFindThemeByName($name);
            }
            foreach ($this->resolve($configuration, $configurationCollection, $onlySourceFiles, $configFileResolver, $nextIncluded) as $item) {
                $resolvedFiles->add($item);
            }
        }

        return $resolvedFiles;
    }

    private function isInclude(string $file): bool
    {
        return str_starts_with($file, '@');
    }

    private function convertPathsToAbsolute(FrontendPluginConfiguration $themeConfig, FileCollection $files): void
    {
        foreach ($files->getElements() as $file) {
            if ($this->isInclude($file->getFilepath())) {
                continue;
            }

            $fs = $this->themeFilesystemResolver->getFilesystemForFrontendConfig($themeConfig);
            if ($fs->has('Resources', $file->getFilepath())) {
                $file->setFilepath($fs->realpath('Resources', $file->getFilepath()));
            }

            $mapping = $file->getResolveMapping();

            foreach ($mapping as $key => $val) {
                if ($fs->has('Resources', $val)) {
                    $mapping[$key] = $fs->realpath('Resources', $val);
                }
            }

            $file->setResolveMapping($mapping);
        }
    }
}
