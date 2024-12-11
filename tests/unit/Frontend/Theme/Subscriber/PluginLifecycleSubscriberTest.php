<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Frontend\Theme\Subscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Plugin;
use Cicada\Core\Framework\Plugin\Context\ActivateContext;
use Cicada\Core\Framework\Plugin\Event\PluginPostActivateEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPostDeactivateEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPostDeactivationFailedEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPostUninstallEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPreDeactivateEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPreUninstallEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPreUpdateEvent;
use Cicada\Core\Framework\Plugin\PluginEntity;
use Cicada\Core\Framework\Plugin\PluginLifecycleService;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\FrontendPluginConfigurationFactory;
use Cicada\Frontend\Theme\FrontendPluginRegistry;
use Cicada\Frontend\Theme\Subscriber\PluginLifecycleSubscriber;
use Cicada\Frontend\Theme\ThemeLifecycleHandler;
use Cicada\Frontend\Theme\ThemeLifecycleService;

/**
 * @internal
 */
#[CoversClass(PluginLifecycleSubscriber::class)]
class PluginLifecycleSubscriberTest extends TestCase
{
    private PluginLifecycleSubscriber $pluginSubscriber;

    protected function setUp(): void
    {
        $this->pluginSubscriber = new PluginLifecycleSubscriber(
            $this->createMock(FrontendPluginRegistry::class),
            '',
            $this->createMock(FrontendPluginConfigurationFactory::class),
            $this->createMock(ThemeLifecycleHandler::class),
            $this->createMock(ThemeLifecycleService::class),
        );
    }

    public function testGetSubscribedEvents(): void
    {
        static::assertEquals(
            [
                PluginPostActivateEvent::class => 'pluginPostActivate',
                PluginPreUpdateEvent::class => 'pluginUpdate',
                PluginPreDeactivateEvent::class => 'pluginDeactivateAndUninstall',
                PluginPostDeactivateEvent::class => 'pluginPostDeactivate',
                PluginPostDeactivationFailedEvent::class => 'pluginPostDeactivateFailed',
                PluginPreUninstallEvent::class => 'pluginDeactivateAndUninstall',
                PluginPostUninstallEvent::class => 'pluginPostUninstall',
            ],
            PluginLifecycleSubscriber::getSubscribedEvents()
        );
    }

    public function testSkipPostCompile(): void
    {
        $context = Context::createDefaultContext();
        $context->addState(PluginLifecycleService::STATE_SKIP_ASSET_BUILDING);
        $activateContextMock = $this->createMock(ActivateContext::class);
        $activateContextMock->expects(static::once())->method('getContext')->willReturn($context);
        $eventMock = $this->createMock(PluginPostActivateEvent::class);
        $eventMock->expects(static::once())->method('getContext')->willReturn($activateContextMock);
        $eventMock->expects(static::never())->method('getPlugin');

        $this->pluginSubscriber->pluginPostActivate($eventMock);
    }

    public function testPluginPostActivate(): void
    {
        $pluginMock = new PluginEntity();
        $pluginMock->setPath('');
        $pluginMock->setBaseClass(FakePlugin::class);
        $eventMock = $this->createMock(PluginPostActivateEvent::class);
        $eventMock->expects(static::exactly(2))->method('getPlugin')->willReturn($pluginMock);
        $this->pluginSubscriber->pluginPostActivate($eventMock);
    }

    public function testPluginPostDeactivateFailed(): void
    {
        $pluginMock = new PluginEntity();
        $pluginMock->setPath('');
        $pluginMock->setBaseClass(FakePlugin::class);

        $eventMock = $this->createMock(PluginPostDeactivationFailedEvent::class);
        $eventMock->expects(static::exactly(2))->method('getPlugin')->willReturn($pluginMock);
        $this->pluginSubscriber->pluginPostDeactivateFailed($eventMock);
    }
}

/**
 * @internal
 */
class FakePlugin extends Plugin
{
}
