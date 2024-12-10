<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme;

use Cicada\Core\Framework\Bundle;
use Cicada\Core\Framework\Log\Package;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\AbstractFrontendPluginConfigurationFactory;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\FrontendPluginConfigurationCollection;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Service\ResetInterface;

#[Package('frontend')]
class FrontendPluginRegistry implements FrontendPluginRegistryInterface, ResetInterface
{
    final public const BASE_THEME_NAME = 'Frontend';

    private ?FrontendPluginConfigurationCollection $pluginConfigurations = null;

    /**
     * @internal
     */
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly AbstractFrontendPluginConfigurationFactory $pluginConfigurationFactory,
    ) {
    }

    public function getConfigurations(): FrontendPluginConfigurationCollection
    {
        if ($this->pluginConfigurations) {
            return $this->pluginConfigurations;
        }

        $this->pluginConfigurations = new FrontendPluginConfigurationCollection();

        $this->addPluginConfigs();

        return $this->pluginConfigurations ?? new FrontendPluginConfigurationCollection();
    }

    public function reset(): void
    {
        $this->pluginConfigurations = null;
    }

    private function addPluginConfigs(): void
    {
        foreach ($this->kernel->getBundles() as $bundle) {
            if (!$bundle instanceof Bundle) {
                continue;
            }

            $config = $this->pluginConfigurationFactory->createFromBundle($bundle);

            $this->pluginConfigurations === null ?: $this->pluginConfigurations->add($config);
        }
    }
}
