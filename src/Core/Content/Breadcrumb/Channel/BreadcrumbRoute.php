<?php declare(strict_types=1);

namespace Cicada\Core\Content\Breadcrumb\Channel;

use Cicada\Core\Content\Breadcrumb\Struct\Breadcrumb;
use Cicada\Core\Content\Breadcrumb\Struct\BreadcrumbCollection;
use Cicada\Core\Content\Category\Service\CategoryBreadcrumbBuilder;
use Cicada\Core\Content\Product\Exception\ProductNotFoundException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @experimental stableVersion:v6.7.0 feature:BREADCRUMB_STORE_API
 */
#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('content')]
class BreadcrumbRoute extends AbstractBreadcrumbRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly CategoryBreadcrumbBuilder $breadcrumbBuilder,
    ) {
    }

    public function getDecorated(): AbstractBreadcrumbRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/breadcrumb/{id}', name: 'store-api.breadcrumb', requirements: ['id' => '[0-9a-f]{32}'], methods: ['GET'])]
    public function load(Request $request, ChannelContext $channelContext): BreadcrumbRouteResponse
    {
        $id = $request->get('id', '');
        $type = $request->get('type', 'product');
        if ($type === 'category') {
            $categories = $this->getCategories($id, $channelContext);
        } else {
            $categories = $this->tryToGetCategoriesFromProductOrCategory(
                $id,
                $request->get('referrerCategoryId', ''),
                $channelContext
            );
        }

        $breadcrumb = new BreadcrumbCollection($categories);

        return new BreadcrumbRouteResponse($breadcrumb);
    }

    /**
     * @return array<int, Breadcrumb>
     */
    private function getCategories(string $id, ChannelContext $channelContext): array
    {
        $category = $this->breadcrumbBuilder->loadCategory($id, $channelContext->getContext());

        if ($category === null) {
            return [];
        }

        return $this->breadcrumbBuilder->getCategoryBreadcrumbUrls(
            $category,
            $channelContext->getContext(),
            $channelContext->getChannel()
        );
    }

    /**
     * Simple helper function to retry with category type if product is not found
     *
     * @return array<int, Breadcrumb>
     */
    private function tryToGetCategoriesFromProductOrCategory(string $id, string $referrerCategoryId, ChannelContext $channelContext): array
    {
        try {
            $categories = $this->breadcrumbBuilder->getProductBreadcrumbUrls($id, $referrerCategoryId, $channelContext);
        } catch (ProductNotFoundException) {
            $categories = $this->getCategories($id, $channelContext);
        }

        return $categories;
    }
}
