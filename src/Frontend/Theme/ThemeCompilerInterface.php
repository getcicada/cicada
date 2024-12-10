<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\FrontendPluginConfiguration;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\FrontendPluginConfigurationCollection;

#[Package('frontend')]
interface ThemeCompilerInterface
{
    public function compileTheme(
        string $channelId,
        string $themeId,
        FrontendPluginConfiguration $themeConfig,
        FrontendPluginConfigurationCollection $configurationCollection,
        bool $withAssets,
        Context $context
    ): void;
}
