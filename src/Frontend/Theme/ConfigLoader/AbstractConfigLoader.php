<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme\ConfigLoader;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\FrontendPluginConfiguration;

#[Package('frontend')]
abstract class AbstractConfigLoader
{
    abstract public function getDecorated(): AbstractConfigLoader;

    abstract public function load(string $themeId, Context $context): FrontendPluginConfiguration;
}
