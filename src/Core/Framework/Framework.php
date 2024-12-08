<?php declare(strict_types=1);

namespace Cicada\Core\Framework;

use Cicada\Core\Framework\DependencyInjection\FrameworkExtension;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * @internal
 */
#[Package('core')]
class Framework extends Bundle
{
    public function getTemplatePriority(): int
    {
        return -1;
    }

    public function getContainerExtension(): Extension
    {
        return new FrameworkExtension();
    }

    public function build(ContainerBuilder $container): void
    {
        $container->setParameter('locale', 'zh-CN');
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/DependencyInjection/'));
        parent::build($container);
        $this->buildDefaultConfig($container);
    }

}