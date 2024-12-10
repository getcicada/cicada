<?php
declare(strict_types=1);

namespace Cicada\Frontend\Theme\ConfigLoader;

use League\Flysystem\FilesystemOperator;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\File;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\FileCollection;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\FrontendPluginConfiguration;

#[Package('frontend')]
class StaticFileConfigLoader extends AbstractConfigLoader
{
    /**
     * @internal
     */
    public function __construct(private readonly FilesystemOperator $filesystem)
    {
    }

    public function getDecorated(): AbstractConfigLoader
    {
        throw new DecorationPatternException(self::class);
    }

    public function load(string $themeId, Context $context): FrontendPluginConfiguration
    {
        $path = \sprintf('theme-config/%s.json', $themeId);

        if (!$this->filesystem->fileExists($path)) {
            throw new \RuntimeException('Cannot find theme configuration. Did you run bin/console theme:dump');
        }

        $fileContent = $this->filesystem->read($path);
        \assert(\is_string($fileContent));
        $fileObject = json_decode($fileContent, true, 512, \JSON_THROW_ON_ERROR);

        $fileObject = $this->prepareCollections($fileObject);

        $config = new FrontendPluginConfiguration('');
        $config->assign($fileObject);

        return $config;
    }

    private function prepareCollections(array $fileObject): array
    {
        $fileObject['styleFiles'] = array_map(fn (array $file) => (new File(''))->assign($file), $fileObject['styleFiles']);

        $fileObject['scriptFiles'] = array_map(fn (array $file) => (new File(''))->assign($file), $fileObject['scriptFiles']);

        $fileObject['styleFiles'] = new FileCollection($fileObject['styleFiles']);
        $fileObject['scriptFiles'] = new FileCollection($fileObject['scriptFiles']);

        return $fileObject;
    }
}
