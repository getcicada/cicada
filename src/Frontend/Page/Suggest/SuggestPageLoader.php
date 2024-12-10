<?php declare(strict_types=1);

namespace Cicada\Frontend\Page\Suggest;

use Cicada\Core\Content\Category\Exception\CategoryNotFoundException;
use Cicada\Core\Content\Product\Channel\Suggest\AbstractProductSuggestRoute;
use Cicada\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Frontend\Page\GenericPageLoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Do not use direct or indirect repository calls in a PageLoader. Always use a store-api route to get or put data.
 */
#[Package('services-settings')]
class SuggestPageLoader
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AbstractProductSuggestRoute $productSuggestRoute,
        private readonly GenericPageLoaderInterface $genericLoader
    ) {
    }

    /**
     * @throws CategoryNotFoundException
     * @throws InconsistentCriteriaIdsException
     * @throws RoutingException
     */
    public function load(Request $request, ChannelContext $channelContext): SuggestPage
    {
        $page = $this->genericLoader->load($request, $channelContext);

        $page = SuggestPage::createFrom($page);

        $criteria = new Criteria();
        $criteria->setLimit(10);
        $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);
        $page->setSearchResult(
            $this->productSuggestRoute
                ->load($request, $channelContext, $criteria)
                ->getListingResult()
        );

        $page->setSearchTerm((string) $request->query->get('search'));

        $this->eventDispatcher->dispatch(
            new SuggestPageLoadedEvent($page, $channelContext, $request)
        );

        return $page;
    }
}
