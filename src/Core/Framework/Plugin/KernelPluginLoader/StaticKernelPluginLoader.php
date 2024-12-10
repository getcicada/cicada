<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Plugin\KernelPluginLoader;

use Composer\Autoload\ClassLoader;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class StaticKernelPluginLoader extends KernelPluginLoader
{
    public function __construct(
        ClassLoader $classLoader,
        ?string $pluginDir = null,
        array $plugins = []
    ) {
        parent::__construct($classLoader, $pluginDir);

        $this->pluginInfos = $plugins;
    }

    protected function loadPluginInfos(): void
    {
        // loaded in constructor
    }
}
