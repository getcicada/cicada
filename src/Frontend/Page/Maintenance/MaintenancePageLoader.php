<?php declare(strict_types=1);

namespace Cicada\Frontend\Page\Maintenance;

use Cicada\Core\Content\Cms\CmsPageCollection;
use Cicada\Core\Content\Cms\Exception\PageNotFoundException;
use Cicada\Core\Content\Cms\Channel\ChannelCmsPageLoaderInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Frontend\Page\GenericPageLoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Do not use direct or indirect repository calls in a PageLoader. Always use a store-api route to get or put data.
 */
#[Package('frontend')]
class MaintenancePageLoader
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ChannelCmsPageLoaderInterface $cmsPageLoader,
        private readonly GenericPageLoaderInterface $genericLoader,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @throws PageNotFoundException
     */
    public function load(string $cmsErrorLayoutId, Request $request, ChannelContext $context): MaintenancePage
    {
        try {
            $page = $this->genericLoader->load($request, $context);
            $page = MaintenancePage::createFrom($page);

            /** @var CmsPageCollection $pages */
            $pages = $this->cmsPageLoader->load($request, new Criteria([$cmsErrorLayoutId]), $context)->getEntities();

            if (!$pages->has($cmsErrorLayoutId)) {
                throw new PageNotFoundException($cmsErrorLayoutId);
            }

            $page->setCmsPage($pages->get($cmsErrorLayoutId));

            $this->eventDispatcher->dispatch(new MaintenancePageLoadedEvent($page, $context, $request));

            return $page;
        } catch (\Exception) {
            throw new PageNotFoundException($cmsErrorLayoutId);
        }
    }
}
