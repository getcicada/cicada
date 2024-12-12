<?php declare(strict_types=1);

namespace Cicada\Frontend\Controller;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Frontend\Page\Navigation\NavigationPageLoaderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 * Do not use direct or indirect repository calls in a controller. Always use a store-api route to get or put data
 */
#[Route(defaults: ['_routeScope' => ['frontend']])]
#[Package('frontend')]
class NavigationController extends FrontendController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly NavigationPageLoaderInterface $navigationPageLoader,
    ) {
    }

    #[Route(path: '/', name: 'frontend.home.page', options: ['seo' => true], defaults: ['_httpCache' => true], methods: ['GET'])]
    public function home(Request $request, ChannelContext $context): ?Response
    {
        $page = $this->navigationPageLoader->load($request, $context);


        return $this->renderFrontend('@Frontend/frontend/page/content/index.html.twig', ['page' => $page]);
    }

    #[Route(path: '/navigation/{navigationId}', name: 'frontend.navigation.page', options: ['seo' => true], defaults: ['_httpCache' => true], methods: ['GET'])]
    public function index(ChannelContext $context, Request $request): Response
    {
        $page = $this->navigationPageLoader->load($request, $context);


        return $this->renderFrontend('@Frontend/frontend/page/content/index.html.twig', ['page' => $page]);
    }
}
