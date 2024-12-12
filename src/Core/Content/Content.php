<?php declare(strict_types=1);

namespace Cicada\Core\Content;

use Cicada\Core\Framework\Bundle;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * @internal
 */
#[Package('core')]
class Content extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/DependencyInjection/'));
        $loader->load('media.xml');
        $loader->load('media_path.xml');
        $loader->load('landing_page.xml');
        $loader->load('cms.xml');
        $loader->load('sitemap.xml');
        $loader->load('category.xml');
        $loader->load('blog.xml');
    }
}