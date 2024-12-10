<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme;

use Cicada\Core\Framework\Adapter\Filesystem\Plugin\CopyBatchInput;
use Cicada\Core\Framework\Log\Package;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\File;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\FrontendPluginConfiguration;

/**
 * @deprecated tag:v6.7.0 Will be removed.
 */
#[Package('frontend')]
interface ThemeFileImporterInterface
{
    public function fileExists(string $filePath): bool;

    public function getRealPath(string $filePath): string;

    public function getConcatenableStylePath(File $file, FrontendPluginConfiguration $configuration): string;

    public function getConcatenableScriptPath(File $file, FrontendPluginConfiguration $configuration): string;

    /**
     * @return CopyBatchInput[]
     */
    public function getCopyBatchInputsForAssets(string $assetPath, string $outputPath, FrontendPluginConfiguration $configuration): array;
}
