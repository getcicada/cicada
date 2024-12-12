<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\Channel;

use Cicada\Core\Content\Sitemap\Exception\AlreadyLockedException;
use Cicada\Core\Content\Sitemap\Service\SitemapExporterInterface;
use Cicada\Core\Content\Sitemap\Service\SitemapListerInterface;
use Cicada\Core\Content\Sitemap\Struct\SitemapCollection;
use Cicada\Core\Framework\Adapter\Cache\Event\AddCacheTagEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('services-settings')]
class SitemapRoute extends AbstractSitemapRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly SitemapListerInterface $sitemapLister,
        private readonly SystemConfigService $systemConfigService,
        private readonly SitemapExporterInterface $sitemapExporter,
        private readonly EventDispatcherInterface $dispatcher
    ) {
    }

    public static function buildName(string $id): string
    {
        return 'sitemap-route-' . $id;
    }

    #[Route(path: '/store-api/sitemap', name: 'store-api.sitemap', methods: ['GET', 'POST'])]
    public function load(Request $request, ChannelContext $context): SitemapRouteResponse
    {
        $this->dispatcher->dispatch(new AddCacheTagEvent(self::buildName($context->getChannelId())));

        $sitemaps = $this->sitemapLister->getSitemaps($context);

        if ($this->systemConfigService->getInt('core.sitemap.sitemapRefreshStrategy') !== SitemapExporterInterface::STRATEGY_LIVE) {
            return new SitemapRouteResponse(new SitemapCollection($sitemaps));
        }

        // Close session to prevent session locking from waiting in case there is another request coming in
        if ($request->hasSession() && session_status() === \PHP_SESSION_ACTIVE) {
            $request->getSession()->save();
        }

        try {
            $this->generateSitemap($context, true);
        } catch (AlreadyLockedException) {
            // Silent catch, lock couldn't be acquired. Some other process already generates the sitemap.
        }

        $sitemaps = $this->sitemapLister->getSitemaps($context);

        return new SitemapRouteResponse(new SitemapCollection($sitemaps));
    }

    public function getDecorated(): AbstractSitemapRoute
    {
        throw new DecorationPatternException(self::class);
    }

    private function generateSitemap(ChannelContext $channelContext, bool $force, ?string $lastProvider = null, ?int $offset = null): void
    {
        $result = $this->sitemapExporter->generate($channelContext, $force, $lastProvider, $offset);
        if ($result->isFinish() === false) {
            $this->generateSitemap($channelContext, $force, $result->getProvider(), $result->getOffset());
        }
    }
}