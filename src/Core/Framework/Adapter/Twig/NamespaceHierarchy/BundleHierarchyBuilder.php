<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Adapter\Twig\NamespaceHierarchy;

use Doctrine\DBAL\Connection;
use Cicada\Core\Framework\Bundle;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpKernel\KernelInterface;

#[Package('core')]
class BundleHierarchyBuilder implements TemplateNamespaceHierarchyBuilderInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly Connection $connection
    ) {
    }

    public function buildNamespaceHierarchy(array $namespaceHierarchy): array
    {
        $bundles = [];

        foreach ($this->kernel->getBundles() as $bundle) {
            if (!$bundle instanceof Bundle) {
                continue;
            }

            $bundlePath = $bundle->getPath();

            $directory = $bundlePath . '/Resources/views';

            if (!file_exists($directory)) {
                continue;
            }

            $bundles[$bundle->getName()] = $bundle->getTemplatePriority();
        }

        $bundles = array_reverse($bundles);

        $extensions = array_merge($bundles);
        asort($extensions);

        foreach ($bundles as $name => ['version' => $version]) {
            $extensions[$name] = $version;
        }

        return array_merge(
            $extensions,
            $namespaceHierarchy
        );
    }
}
