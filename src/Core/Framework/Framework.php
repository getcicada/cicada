<?php declare(strict_types=1);

namespace Cicada\Core\Framework;

use Cicada\Core\Framework\Adapter\Cache\ReverseProxy\ReverseProxyCompilerPass;
use Cicada\Core\Framework\Adapter\Redis\RedisConnectionsCompilerPass;
use Cicada\Core\Framework\DataAbstractionLayer\AttributeEntityCompiler;
use Cicada\Core\Framework\DependencyInjection\CompilerPass\AssetBundleRegistrationCompilerPass;
use Cicada\Core\Framework\DependencyInjection\CompilerPass\AssetRegistrationCompilerPass;
use Cicada\Core\Framework\DependencyInjection\CompilerPass\AttributeEntityCompilerPass;
use Cicada\Core\Framework\DependencyInjection\CompilerPass\AutoconfigureCompilerPass;
use Cicada\Core\Framework\DependencyInjection\CompilerPass\CreateGeneratorScaffoldingCommandPass;
use Cicada\Core\Framework\DependencyInjection\CompilerPass\DefaultTransportCompilerPass;
use Cicada\Core\Framework\DependencyInjection\CompilerPass\DisableTwigCacheWarmerCompilerPass;
use Cicada\Core\Framework\DependencyInjection\CompilerPass\EntityCompilerPass;
use Cicada\Core\Framework\DependencyInjection\CompilerPass\FeatureFlagCompilerPass;
use Cicada\Core\Framework\DependencyInjection\CompilerPass\FilesystemConfigMigrationCompilerPass;
use Cicada\Core\Framework\DependencyInjection\CompilerPass\FrameworkMigrationReplacementCompilerPass;
use Cicada\Core\Framework\DependencyInjection\CompilerPass\HttpCacheConfigCompilerPass;
use Cicada\Core\Framework\DependencyInjection\CompilerPass\MessengerMiddlewareCompilerPass;
use Cicada\Core\Framework\DependencyInjection\CompilerPass\RateLimiterCompilerPass;
use Cicada\Core\Framework\DependencyInjection\CompilerPass\RedisPrefixCompilerPass;
use Cicada\Core\Framework\DependencyInjection\CompilerPass\RouteScopeCompilerPass;
use Cicada\Core\Framework\DependencyInjection\CompilerPass\TwigEnvironmentCompilerPass;
use Cicada\Core\Framework\DependencyInjection\CompilerPass\TwigLoaderConfigCompilerPass;
use Cicada\Core\Framework\DependencyInjection\FrameworkExtension;
use Cicada\Core\Framework\Increment\IncrementerGatewayCompilerPass;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\MessageHandlerCompilerPass;
use Cicada\Core\Framework\Test\DependencyInjection\CompilerPass\ContainerVisibilityCompilerPass;
use Cicada\Core\Framework\Test\RateLimiter\DisableRateLimiterCompilerPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
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
        $loader->load('data-abstraction-layer.xml');
        $loader->load('services.xml');
        $loader->load('acl.xml');
        $loader->load('api.xml');
        $loader->load('message-queue.xml');
        $loader->load('language.xml');
        $loader->load('increment.xml');
        $loader->load('rate-limiter.xml');
        $loader->load('custom-field.xml');
        $loader->load('rule.xml');
        $loader->load('scheduled-task.xml');
        $loader->load('telemetry.xml');
        $loader->load('plugin.xml');
        $loader->load('cache.xml');
        $loader->load('health.xml');
        $loader->load('event.xml');
        $loader->load('store.xml');
        $loader->load('flag.xml');
        $loader->load('seo.xml');
        $loader->load('filesystem.xml');
        if ($container->getParameter('kernel.environment') === 'test') {
            $loader->load('services_test.xml');
        }
        $container->addCompilerPass(new AttributeEntityCompilerPass(new AttributeEntityCompiler()), PassConfig::TYPE_BEFORE_REMOVING, 1000);
        $container->addCompilerPass(new FeatureFlagCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1000);
        $container->addCompilerPass(new EntityCompilerPass());
        $container->addCompilerPass(new DisableTwigCacheWarmerCompilerPass());
        $container->addCompilerPass(new DefaultTransportCompilerPass());
        $container->addCompilerPass(new MessengerMiddlewareCompilerPass());
        $container->addCompilerPass(new TwigLoaderConfigCompilerPass());
        $container->addCompilerPass(new TwigEnvironmentCompilerPass());
        $container->addCompilerPass(new RouteScopeCompilerPass());
        $container->addCompilerPass(new AssetRegistrationCompilerPass());
        $container->addCompilerPass(new AssetBundleRegistrationCompilerPass());
        $container->addCompilerPass(new FilesystemConfigMigrationCompilerPass());
        $container->addCompilerPass(new RateLimiterCompilerPass());
        $container->addCompilerPass(new IncrementerGatewayCompilerPass());
        $container->addCompilerPass(new ReverseProxyCompilerPass());
        $container->addCompilerPass(new RedisPrefixCompilerPass(), PassConfig::TYPE_BEFORE_REMOVING, 0);
        $container->addCompilerPass(new AutoconfigureCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1000);
        $container->addCompilerPass(new HttpCacheConfigCompilerPass());
        $container->addCompilerPass(new MessageHandlerCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1000);
        $container->addCompilerPass(new CreateGeneratorScaffoldingCommandPass());
        $container->addCompilerPass(new RedisConnectionsCompilerPass());

        if ($container->getParameter('kernel.environment') === 'test') {
            $container->addCompilerPass(new DisableRateLimiterCompilerPass());
            $container->addCompilerPass(new ContainerVisibilityCompilerPass());
        }

        $container->addCompilerPass(new FrameworkMigrationReplacementCompilerPass());


        parent::build($container);
        $this->buildDefaultConfig($container);
    }

}