<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme;

use Cicada\Core\Framework\Bundle;
use Cicada\Core\Framework\Log\Package;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\AbstractFrontendPluginConfigurationFactory;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\FrontendPluginConfiguration;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\FrontendPluginConfigurationCollection;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Service\ResetInterface;
/**
 * @internal
 */
#[Package('frontend')]
class FrontendPluginRegistry implements ResetInterface
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

    /**
     * This method loads and parses all theme.json files from all plugins and apps
     * especially for apps where the source can be stored remotely this is expensive and therefore
     * should be used when really all configurations are needed, e.g. during theme compile
     */
    public function getConfigurations(): FrontendPluginConfigurationCollection
    {
        if ($this->pluginConfigurations) {
            return $this->pluginConfigurations;
        }

        $this->pluginConfigurations = new FrontendPluginConfigurationCollection();

        $this->addPluginConfigs();

        return $this->pluginConfigurations ?? new FrontendPluginConfigurationCollection();
    }

    /**
     * used to fetch one particular config without loading and parsing all else
     */
    public function getByTechnicalName(string $technicalName): ?FrontendPluginConfiguration
    {
        if ($this->pluginConfigurations) {
            return $this->pluginConfigurations->getByTechnicalName($technicalName);
        }

        return $this->getPluginConfigByTechnicalName($technicalName);
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

    private function getPluginConfigByTechnicalName(string $technicalName): ?FrontendPluginConfiguration
    {
        foreach ($this->kernel->getBundles() as $bundle) {
            if (!$bundle instanceof Bundle) {
                continue;
            }

            if ($bundle->getName() !== $technicalName) {
                continue;
            }

            return $this->pluginConfigurationFactory->createFromBundle($bundle);
        }

        return null;
    }
}
