<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme\FrontendPluginConfiguration;

use Cicada\Core\Framework\Bundle;
use Cicada\Core\Framework\Log\Package;

#[Package('frontend')]
abstract class AbstractFrontendPluginConfigurationFactory
{
    abstract public function getDecorated(): AbstractFrontendPluginConfigurationFactory;

    abstract public function createFromBundle(Bundle $bundle): FrontendPluginConfiguration;

    abstract public function createFromApp(string $appName, string $appPath): FrontendPluginConfiguration;

    /**
     * @param array<string, mixed> $data
     */
    abstract public function createFromThemeJson(string $name, array $data, string $path): FrontendPluginConfiguration;
}
