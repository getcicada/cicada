<?php declare(strict_types=1);

namespace Cicada\Frontend\Page\LandingPage;

use Cicada\Core\Content\Cms\Exception\PageNotFoundException;
use Cicada\Core\Content\LandingPage\Channel\AbstractLandingPageRoute;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Frontend\Page\GenericPageLoaderInterface;
use Cicada\Frontend\Page\MetaInformation;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Do not use direct or indirect repository calls in a PageLoader. Always use a store-api route to get or put data.
 */
#[Package('frontend')]
class LandingPageLoader
{
    /**
     * @internal
     */
    public function __construct(
        private readonly GenericPageLoaderInterface $genericPageLoader,
        private readonly AbstractLandingPageRoute $landingPageRoute,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @throws PageNotFoundException
     */
    public function load(Request $request, ChannelContext $context): LandingPage
    {
        $landingPageId = $request->attributes->get('landingPageId');
        if (!$landingPageId) {
            throw RoutingException::missingRequestParameter('landingPageId', '/landingPageId');
        }

        $landingPage = $this->landingPageRoute->load($landingPageId, $request, $context)->getLandingPage();

        if ($landingPage->getCmsPage() === null) {
            throw new PageNotFoundException($landingPageId);
        }

        $page = $this->genericPageLoader->load($request, $context);
        $page = LandingPage::createFrom($page);

        $page->setLandingPage($landingPage);

        $metaInformation = new MetaInformation();
        $metaTitle = $landingPage->getMetaTitle() ?? $landingPage->getName();
        $metaInformation->setMetaTitle($metaTitle ?? '');
        $metaInformation->setMetaDescription($landingPage->getMetaDescription() ?? '');
        $metaInformation->setMetaKeywords($landingPage->getKeywords() ?? '');
        $page->setMetaInformation($metaInformation);

        $this->eventDispatcher->dispatch(
            new LandingPageLoadedEvent($page, $context, $request)
        );

        return $page;
    }
}