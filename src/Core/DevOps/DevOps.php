<?php declare(strict_types=1);

namespace Cicada\Core\DevOps;

use Cicada\Core\Framework\Bundle;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * @internal
 */
#[Package('core')]
class DevOps extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/DependencyInjection'));
        $loader->load('services.xml');

        $environment = $container->getParameter('kernel.environment');

        if ($environment === 'dev') {
            $loader->load('services_dev.xml');
        }
    }
}