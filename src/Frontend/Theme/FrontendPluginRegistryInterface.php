<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme;

use Cicada\Core\Framework\Log\Package;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\FrontendPluginConfigurationCollection;

#[Package('frontend')]
interface FrontendPluginRegistryInterface
{
    public function getConfigurations(): FrontendPluginConfigurationCollection;
}
