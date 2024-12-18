<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Seo\SeoUrlRoute;

use Doctrine\DBAL\Connection;
use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Content\Category\CategoryEvents;
use Cicada\Core\Content\Category\Event\CategoryIndexerEvent;
use Cicada\Core\Content\LandingPage\Event\LandingPageIndexerEvent;
use Cicada\Core\Content\LandingPage\LandingPageEvents;
use Cicada\Core\Content\Blog\Events\BlogIndexerEvent;
use Cicada\Core\Content\Blog\BlogEvents;
use Cicada\Core\Content\Seo\SeoUrlUpdater;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('content')]
class SeoUrlUpdateListener implements EventSubscriberInterface
{
    final public const CATEGORY_SEO_URL_UPDATER = 'category.seo-url';
    final public const BLOG_SEO_URL_UPDATER = 'blog.seo-url';
    final public const LANDING_PAGE_SEO_URL_UPDATER = 'landing_page.seo-url';

    /**
     * @internal
     */
    public function __construct(
        private readonly SeoUrlUpdater $seoUrlUpdater,
        private readonly Connection $connection
    ) {
    }

    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            BlogEvents::BLOG_INDEXER_EVENT => 'updateBlogUrls',
            CategoryEvents::CATEGORY_INDEXER_EVENT => 'updateCategoryUrls',
            LandingPageEvents::LANDING_PAGE_INDEXER_EVENT => 'updateLandingPageUrls',
        ];
    }

    public function updateCategoryUrls(CategoryIndexerEvent $event): void
    {
        if (\in_array(self::CATEGORY_SEO_URL_UPDATER, $event->getSkip(), true)) {
            return;
        }

        $ids = array_values($event->getIds());

        if (!$event->isFullIndexing) {
            $ids = array_merge($ids, $this->getCategoryChildren($ids));
        }

        $this->seoUrlUpdater->update(NavigationPageSeoUrlRoute::ROUTE_NAME, $ids);
    }

    public function updateBlogUrls(BlogIndexerEvent $event): void
    {
        if (\in_array(self::BLOG_SEO_URL_UPDATER, $event->getSkip(), true)) {
            return;
        }

        $this->seoUrlUpdater->update(BlogPageSeoUrlRoute::ROUTE_NAME, array_values($event->getIds()));
    }

    public function updateLandingPageUrls(LandingPageIndexerEvent $event): void
    {
        if (\in_array(self::LANDING_PAGE_SEO_URL_UPDATER, $event->getSkip(), true)) {
            return;
        }

        $this->seoUrlUpdater->update(LandingPageSeoUrlRoute::ROUTE_NAME, array_values($event->getIds()));
    }

    /**
     * @param array<string> $ids
     *
     * @return array<string>
     */
    private function getCategoryChildren(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $query = $this->connection->createQueryBuilder();

        $query->select('category.id');
        $query->from('category');

        foreach ($ids as $id) {
            $key = 'id' . $id;
            $query->orWhere('category.type != :type AND category.path LIKE :' . $key);
            $query->setParameter($key, '%' . $id . '%');
        }

        $query->setParameter('type', CategoryDefinition::TYPE_LINK);

        $children = $query->executeQuery()->fetchFirstColumn();

        if (!$children) {
            return [];
        }

        $ids = Uuid::fromBytesToHexList($children);

        return $ids;
    }
}
