<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category\Channel;

use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Content\Category\CategoryEntity;
use Cicada\Core\Content\Category\CategoryException;
use Cicada\Core\Content\Cms\CmsPageEntity;
use Cicada\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Cicada\Core\Content\Cms\Channel\ChannelCmsPageLoaderInterface;
use Cicada\Core\Framework\Adapter\Cache\Event\AddCacheTagEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\System\Channel\Entity\ChannelRepository;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('content')]
class CategoryRoute extends AbstractCategoryRoute
{
    final public const HOME = 'home';

    /**
     * @internal
     */
    public function __construct(
        private readonly ChannelRepository $categoryRepository,
        private readonly ChannelCmsPageLoaderInterface $cmsPageLoader,
        private readonly CategoryDefinition $categoryDefinition,
        private readonly EventDispatcherInterface $dispatcher
    ) {
    }

    public static function buildName(string $id): string
    {
        return 'category-route-' . $id;
    }

    public function getDecorated(): AbstractCategoryRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/category/{navigationId}', name: 'store-api.category.detail', methods: ['GET', 'POST'])]
    public function load(string $navigationId, Request $request, ChannelContext $context): CategoryRouteResponse
    {
        $this->dispatcher->dispatch(new AddCacheTagEvent(self::buildName($navigationId)));

        if ($navigationId === self::HOME) {
            $navigationId = $context->getChannel()->getNavigationCategoryId();
            $request->attributes->set('navigationId', $navigationId);
            $routeParams = $request->attributes->get('_route_params', []);
            $routeParams['navigationId'] = $navigationId;
            $request->attributes->set('_route_params', $routeParams);
        }

        $category = $this->loadCategory($navigationId, $context);

        if (($category->getType() === CategoryDefinition::TYPE_FOLDER
                || $category->getType() === CategoryDefinition::TYPE_LINK)
            && $context->getChannel()->getNavigationCategoryId() !== $navigationId
        ) {
            throw CategoryException::categoryNotFound($navigationId);
        }

        $pageId = $category->getCmsPageId();
        $slotConfig = $category->getTranslation('slotConfig');

        $channel = $context->getChannel();
        if ($category->getId() === $channel->getNavigationCategoryId() && $channel->getHomeCmsPageId()) {
            $pageId = $channel->getHomeCmsPageId();
            $slotConfig = $channel->getTranslation('homeSlotConfig');
        }

        if (!$pageId) {
            return new CategoryRouteResponse($category);
        }

        $resolverContext = new EntityResolverContext($context, $request, $this->categoryDefinition, $category);

        $pages = $this->cmsPageLoader->load(
            $request,
            $this->createCriteria($pageId, $request),
            $context,
            $slotConfig,
            $resolverContext
        );

        if (!$pages->has($pageId)) {
            throw CategoryException::pageNotFound($pageId);
        }

        /** @var CmsPageEntity $page */
        $page = $pages->get($pageId);
        $category->setCmsPage($page);
        $category->setCmsPageId($pageId);

        return new CategoryRouteResponse($category);
    }

    private function loadCategory(string $categoryId, ChannelContext $context): CategoryEntity
    {
        $criteria = new Criteria([$categoryId]);
        $criteria->setTitle('category::data');

        $criteria->addAssociation('media');

        $category = $this->categoryRepository
            ->search($criteria, $context)
            ->get($categoryId);

        if (!$category instanceof CategoryEntity) {
            throw CategoryException::categoryNotFound($categoryId);
        }

        return $category;
    }

    private function createCriteria(string $pageId, Request $request): Criteria
    {
        $criteria = new Criteria([$pageId]);
        $criteria->setTitle('category::cms-page');

        $slots = $request->get('slots');

        if (\is_string($slots)) {
            $slots = explode('|', $slots);
        }

        if (!empty($slots) && \is_array($slots)) {
            $criteria
                ->getAssociation('sections.blocks')
                ->addFilter(new EqualsAnyFilter('slots.id', $slots));
        }

        return $criteria;
    }
}