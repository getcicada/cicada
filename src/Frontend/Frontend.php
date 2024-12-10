<?php declare(strict_types=1);

namespace Cicada\Frontend;

use Cicada\Core\Framework\Bundle;
use Cicada\Core\Framework\Log\Package;
use Cicada\Frontend\DependencyInjection\DisableTemplateCachePass;
use Cicada\Frontend\DependencyInjection\FrontendMigrationReplacementCompilerPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * @internal
 */
#[Package('frontend')]
class Frontend extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $this->buildDefaultConfig($container);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/DependencyInjection'));
        $loader->load('services.xml');
        $loader->load('controller.xml');
        $loader->load('theme.xml');
        $loader->load('seo.xml');

        $container->setParameter('frontendRoot', $this->getPath());

        $container->addCompilerPass(new DisableTemplateCachePass());
        $container->addCompilerPass(new FrontendMigrationReplacementCompilerPass());
    }
}