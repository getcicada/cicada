<?php declare(strict_types=1);

namespace Cicada\Core\Content\Seo\Channel;

use Cicada\Core\Content\Seo\SeoUrl\SeoUrlCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\System\Channel\Entity\ChannelRepository;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('content')]
class SeoUrlRoute extends AbstractSeoUrlRoute
{
    /**
     * @internal
     *
     * @param ChannelRepository<SeoUrlCollection> $channelRepository
     */
    public function __construct(private readonly ChannelRepository $channelRepository)
    {
    }

    public function getDecorated(): AbstractSeoUrlRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/seo-url', name: 'store-api.seo.url', methods: ['GET', 'POST'], defaults: ['_entity' => 'seo_url'])]
    public function load(Request $request, ChannelContext $context, Criteria $criteria): SeoUrlRouteResponse
    {
        return new SeoUrlRouteResponse($this->channelRepository->search($criteria, $context));
    }
}
