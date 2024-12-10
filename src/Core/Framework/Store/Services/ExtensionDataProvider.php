<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Services;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Plugin\PluginCollection;
use Cicada\Core\Framework\Store\Event\InstalledExtensionsListingLoadedEvent;
use Cicada\Core\Framework\Store\Struct\ExtensionCollection;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('member')]
class ExtensionDataProvider extends AbstractExtensionDataProvider
{
    final public const HEADER_NAME_TOTAL_COUNT = 'SW-Meta-Total';

    public function __construct(
        private readonly ExtensionLoader $extensionLoader,
        private readonly EntityRepository $pluginRepository,
        private readonly ExtensionListingLoader $extensionListingLoader,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function getInstalledExtensions(Context $context, bool $loadCloudExtensions = true, ?Criteria $searchCriteria = null): ExtensionCollection
    {
        $pluginCriteria = $searchCriteria ? clone $searchCriteria : new Criteria();
        $pluginCriteria->addAssociation('translations');

        /** @var PluginCollection $installedPlugins */
        $installedPlugins = $this->pluginRepository->search($pluginCriteria, $context)->getEntities();
        $pluginCollection = $this->extensionLoader->loadFromPluginCollection($context, $installedPlugins);

        if ($loadCloudExtensions) {
            $pluginCollection = $this->extensionListingLoader->load($pluginCollection, $context);
        }

        $this->eventDispatcher->dispatch($event = new InstalledExtensionsListingLoadedEvent($pluginCollection, $context));

        return $event->extensionCollection;
    }

    protected function getDecorated(): AbstractExtensionDataProvider
    {
        throw new DecorationPatternException(self::class);
    }
}
