<?php declare(strict_types=1);

namespace Cicada\Core\Content\LandingPage\Channel;

use Cicada\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Cicada\Core\Content\Cms\Channel\Struct\ProductBoxStruct;
use Cicada\Core\Content\Cms\Channel\Struct\ProductSliderStruct;
use Cicada\Core\Content\LandingPage\Event\LandingPageRouteCacheKeyEvent;
use Cicada\Core\Content\LandingPage\Event\LandingPageRouteCacheTagsEvent;
use Cicada\Core\Framework\Adapter\Cache\AbstractCacheTracer;
use Cicada\Core\Framework\Adapter\Cache\CacheValueCompressor;
use Cicada\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGenerator;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\RuleAreas;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Util\Hasher;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @deprecated tag:v6.7.0 - reason:decoration-will-be-removed - Will be removed
 */
#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('frontend')]
class CachedLandingPageRoute extends AbstractLandingPageRoute
{
    /**
     * @internal
     *
     * @param AbstractCacheTracer<LandingPageRouteResponse> $tracer
     * @param array<string> $states
     */
    public function __construct(
        private readonly AbstractLandingPageRoute $decorated,
        private readonly CacheInterface $cache,
        private readonly EntityCacheKeyGenerator $generator,
        private readonly AbstractCacheTracer $tracer,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly array $states
    ) {
    }

    public static function buildName(string $id): string
    {
        return 'landing-page-route-' . $id;
    }

    public function getDecorated(): AbstractLandingPageRoute
    {
        return $this->decorated;
    }

    #[Route(path: '/store-api/landing-page/{landingPageId}', name: 'store-api.landing-page.detail', methods: ['POST'])]
    public function load(string $landingPageId, Request $request, ChannelContext $context): LandingPageRouteResponse
    {
        if (Feature::isActive('cache_rework')) {
            return $this->getDecorated()->load($landingPageId, $request, $context);
        }

        if ($context->hasState(...$this->states)) {
            return $this->getDecorated()->load($landingPageId, $request, $context);
        }

        $key = $this->generateKey($landingPageId, $request, $context);

        if ($key === null) {
            return $this->getDecorated()->load($landingPageId, $request, $context);
        }

        $value = $this->cache->get($key, function (ItemInterface $item) use ($request, $context, $landingPageId) {
            $name = self::buildName($landingPageId);
            $response = $this->tracer->trace($name, fn () => $this->getDecorated()->load($landingPageId, $request, $context));

            $item->tag($this->generateTags($landingPageId, $response, $request, $context));

            return CacheValueCompressor::compress($response);
        });

        return CacheValueCompressor::uncompress($value);
    }

    private function generateKey(string $landingPageId, Request $request, ChannelContext $context): ?string
    {
        $parts = [...$request->query->all(), ...$request->request->all(), ...[$this->generator->getChannelContextHash($context, [RuleAreas::LANDING_PAGE_AREA, RuleAreas::PRODUCT_AREA, RuleAreas::CATEGORY_AREA])]];

        $event = new LandingPageRouteCacheKeyEvent($landingPageId, $parts, $request, $context, null);
        $this->dispatcher->dispatch($event);

        if (!$event->shouldCache()) {
            return null;
        }

        return self::buildName($landingPageId) . '-' . Hasher::hash($event->getParts());
    }

    /**
     * @return array<string>
     */
    private function generateTags(string $landingPageId, LandingPageRouteResponse $response, Request $request, ChannelContext $context): array
    {
        $tags = array_merge(
            $this->tracer->get(self::buildName($landingPageId)),
            $this->extractIds($response),
            [self::buildName($landingPageId)]
        );

        $event = new LandingPageRouteCacheTagsEvent($landingPageId, $tags, $request, $response, $context, null);
        $this->dispatcher->dispatch($event);

        return array_unique(array_filter($event->getTags()));
    }

    /**
     * @return array<string>
     */
    private function extractIds(LandingPageRouteResponse $response): array
    {
        $page = $response->getLandingPage()->getCmsPage();

        if ($page === null) {
            return [];
        }

        $ids = [];
        $streamIds = [];

        $slots = $page->getElementsOfType('product-slider');
        /** @var CmsSlotEntity $slot */
        foreach ($slots as $slot) {
            $slider = $slot->getData();

            if (!$slider instanceof ProductSliderStruct) {
                continue;
            }

            if ($slider->getStreamId() !== null) {
                $streamIds[] = $slider->getStreamId();
            }

            if ($slider->getProducts() === null) {
                continue;
            }
            foreach ($slider->getProducts() as $product) {
                $ids[] = $product->getId();
                $ids[] = $product->getParentId();
            }
        }

        $slots = $page->getElementsOfType('product-box');
        /** @var CmsSlotEntity $slot */
        foreach ($slots as $slot) {
            $box = $slot->getData();

            if (!$box instanceof ProductBoxStruct) {
                continue;
            }
            if ($box->getProduct() === null) {
                continue;
            }

            $ids[] = $box->getProduct()->getId();
            $ids[] = $box->getProduct()->getParentId();
        }

        $ids = array_values(array_unique(array_filter($ids)));

        return [...array_map(EntityCacheKeyGenerator::buildProductTag(...), $ids), ...array_map(EntityCacheKeyGenerator::buildStreamTag(...), $streamIds), ...[EntityCacheKeyGenerator::buildCmsTag($page->getId())]];
    }
}