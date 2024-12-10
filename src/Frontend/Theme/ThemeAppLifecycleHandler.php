<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme;

use Cicada\Core\Framework\App\Event\AppActivatedEvent;
use Cicada\Core\Framework\App\Event\AppChangedEvent;
use Cicada\Core\Framework\App\Event\AppDeactivatedEvent;
use Cicada\Core\Framework\App\Event\AppUpdatedEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\AbstractFrontendPluginConfigurationFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('frontend')]
class ThemeAppLifecycleHandler implements EventSubscriberInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly FrontendPluginRegistryInterface $themeRegistry,
        private readonly AbstractFrontendPluginConfigurationFactory $themeConfigFactory,
        private readonly ThemeLifecycleHandler $themeLifecycleHandler
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AppUpdatedEvent::class => 'handleAppActivationOrUpdate',
            AppActivatedEvent::class => 'handleAppActivationOrUpdate',
            AppDeactivatedEvent::class => 'handleUninstall',
        ];
    }

    public function handleAppActivationOrUpdate(AppChangedEvent $event): void
    {
        $app = $event->getApp();
        if (!$app->isActive()) {
            return;
        }

        $configurationCollection = $this->themeRegistry->getConfigurations();
        $config = $configurationCollection->getByTechnicalName($app->getName());

        if (!$config) {
            $config = $this->themeConfigFactory->createFromApp($app->getName(), $app->getPath());
            $configurationCollection = clone $configurationCollection;
            $configurationCollection->add($config);
        }

        $this->themeLifecycleHandler->handleThemeInstallOrUpdate(
            $config,
            $configurationCollection,
            $event->getContext()
        );
    }

    public function handleUninstall(AppDeactivatedEvent $event): void
    {
        $config = $this->themeRegistry->getConfigurations()->getByTechnicalName($event->getApp()->getName());

        if (!$config) {
            return;
        }

        $this->themeLifecycleHandler->handleThemeUninstall($config, $event->getContext());
    }
}
