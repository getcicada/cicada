<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category\Channel;

use Cicada\Core\Content\Category\CategoryCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\System\Channel\Entity\ChannelRepository;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('content')]
class CategoryListRoute extends AbstractCategoryListRoute
{
    /**
     * @internal
     *
     * @param ChannelRepository<CategoryCollection> $categoryRepository
     */
    public function __construct(private readonly ChannelRepository $categoryRepository)
    {
    }

    public function getDecorated(): AbstractCategoryListRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/category', name: 'store-api.category.search', defaults: ['_entity' => 'category'], methods: ['GET', 'POST'])]
    public function load(Criteria $criteria, ChannelContext $context): CategoryListRouteResponse
    {
        $rootIds = array_filter([
            $context->getChannel()->getNavigationCategoryId(),
            $context->getChannel()->getFooterCategoryId(),
            $context->getChannel()->getServiceCategoryId(),
        ]);

        if (!empty($rootIds)) {
            $filter = new OrFilter();

            foreach ($rootIds as $rootId) {
                $filter->addQuery(new EqualsFilter('id', $rootId));
                $filter->addQuery(new ContainsFilter('path', '|' . $rootId . '|'));
            }

            $criteria->addFilter($filter);
        }

        return new CategoryListRouteResponse($this->categoryRepository->search($criteria, $context));
    }
}