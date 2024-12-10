<?php declare(strict_types=1);

namespace Cicada\Frontend\Controller;

use Cicada\Core\Content\Cms\Channel\AbstractCmsRoute;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 * Do not use direct or indirect repository calls in a controller. Always use a store-api route to get or put data
 */
#[Route(defaults: ['_routeScope' => ['frontend']])]
#[Package('frontend')]
class CmsController extends FrontendController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly AbstractCmsRoute $cmsRoute,
    ) {
    }

    #[Route(path: '/widgets/cms/{id}', name: 'frontend.cms.page', defaults: ['id' => null, 'XmlHttpRequest' => true, '_httpCache' => true], methods: ['GET', 'POST'])]
    public function page(?string $id, Request $request, ChannelContext $channelContext): Response
    {
        if (!$id) {
            throw RoutingException::missingRequestParameter('id');
        }

        $page = $this->cmsRoute->load($id, $request, $channelContext)->getCmsPage();

        $response = $this->renderFrontend('@Frontend/frontend/page/content/detail.html.twig', ['cmsPage' => $page]);
        $response->headers->set('x-robots-tag', 'noindex');

        return $response;
    }
}
