<?php declare(strict_types=1);

namespace Cicada\Core\System;

use Cicada\Core\Framework\Bundle;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\DependencyInjection\CompilerPass\ChannelEntityCompilerPass;
use Cicada\Core\System\DependencyInjection\CompilerPass\RedisNumberRangeIncrementerCompilerPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * @internal
 */
#[Package('core')]
class System extends Bundle
{
    public function getTemplatePriority(): int
    {
        return -1;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/DependencyInjection/'));
        $loader->load('user.xml');
        $loader->load('configuration.xml');
        $loader->load('locale.xml');
        $loader->load('number_range.xml');
        $loader->load('snippet.xml');
        $loader->load('integration.xml');
        $loader->load('channel.xml');
        $container->addCompilerPass(new ChannelEntityCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new RedisNumberRangeIncrementerCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
    }
}