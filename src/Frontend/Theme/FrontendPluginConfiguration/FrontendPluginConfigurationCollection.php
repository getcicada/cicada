<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme\FrontendPluginConfiguration;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Collection;

/**
 * @extends Collection<FrontendPluginConfiguration>
 */
#[Package('frontend')]
class FrontendPluginConfigurationCollection extends Collection
{
    public function __construct(iterable $elements = [])
    {
        parent::__construct();

        foreach ($elements as $element) {
            $this->validateType($element);

            $this->set($element->getTechnicalName(), $element);
        }
    }

    public function add($element): void
    {
        $this->validateType($element);

        $this->set($element->getTechnicalName(), $element);
    }

    public function getByTechnicalName(string $name): ?FrontendPluginConfiguration
    {
        return $this->filter(fn (FrontendPluginConfiguration $config) => $config->getTechnicalName() === $name)->first();
    }

    public function getThemes(): FrontendPluginConfigurationCollection
    {
        return $this->filter(fn (FrontendPluginConfiguration $configuration) => $configuration->getIsTheme());
    }

    public function getNoneThemes(): FrontendPluginConfigurationCollection
    {
        return $this->filter(fn (FrontendPluginConfiguration $configuration) => !$configuration->getIsTheme());
    }

    protected function getExpectedClass(): ?string
    {
        return FrontendPluginConfiguration::class;
    }
}
