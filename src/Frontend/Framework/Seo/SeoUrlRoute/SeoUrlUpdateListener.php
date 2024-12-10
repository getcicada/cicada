<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Seo\SeoUrlRoute;

use Cicada\Core\Content\LandingPage\Event\LandingPageIndexerEvent;
use Cicada\Core\Content\LandingPage\LandingPageEvents;
use Cicada\Core\Content\Seo\SeoUrlUpdater;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('frontend')]
class SeoUrlUpdateListener implements EventSubscriberInterface
{
    final public const LANDING_PAGE_SEO_URL_UPDATER = 'landing_page.seo-url';

    /**
     * @internal
     */
    public function __construct(
        private readonly SeoUrlUpdater $seoUrlUpdater,
    ) {
    }

    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LandingPageEvents::LANDING_PAGE_INDEXER_EVENT => 'updateLandingPageUrls',
        ];
    }
    public function updateLandingPageUrls(LandingPageIndexerEvent $event): void
    {
        if (\in_array(self::LANDING_PAGE_SEO_URL_UPDATER, $event->getSkip(), true)) {
            return;
        }

        $this->seoUrlUpdater->update(LandingPageSeoUrlRoute::ROUTE_NAME, array_values($event->getIds()));
    }
}
