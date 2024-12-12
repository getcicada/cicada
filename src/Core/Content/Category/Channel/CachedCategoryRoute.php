<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category\Channel;

use Cicada\Core\Content\Category\Event\CategoryRouteCacheKeyEvent;
use Cicada\Core\Content\Category\Event\CategoryRouteCacheTagsEvent;
use Cicada\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Cicada\Core\Content\Cms\Channel\Struct\ProductBoxStruct;
use Cicada\Core\Content\Cms\Channel\Struct\ProductSliderStruct;
use Cicada\Core\Framework\Adapter\Cache\AbstractCacheTracer;
use Cicada\Core\Framework\Adapter\Cache\CacheValueCompressor;
use Cicada\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGenerator;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\RuleAreas;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Util\Hasher;
use Cicada\Core\Profiling\Profiler;
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
#[Package('content')]
class CachedCategoryRoute extends AbstractCategoryRoute
{
    /**
     * @internal
     *
     * @param AbstractCacheTracer<CategoryRouteResponse> $tracer
     * @param array<string> $states
     */
    public function __construct(
        private readonly AbstractCategoryRoute $decorated,
        private readonly CacheInterface $cache,
        private readonly EntityCacheKeyGenerator $generator,
        private readonly AbstractCacheTracer $tracer,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly array $states
    ) {
    }

    public static function buildName(string $id): string
    {
        return 'category-route-' . $id;
    }

    public function getDecorated(): AbstractCategoryRoute
    {
        return $this->decorated;
    }

    #[Route(path: '/store-api/category/{navigationId}', name: 'store-api.category.detail', methods: ['GET', 'POST'])]
    public function load(string $navigationId, Request $request, ChannelContext $context): CategoryRouteResponse
    {
        if (Feature::isActive('cache_rework')) {
            return $this->getDecorated()->load($navigationId, $request, $context);
        }

        return Profiler::trace('category-route', function () use ($navigationId, $request, $context) {
            if ($context->hasState(...$this->states)) {
                return $this->getDecorated()->load($navigationId, $request, $context);
            }

            $key = $this->generateKey($navigationId, $request, $context);

            if ($key === null) {
                return $this->getDecorated()->load($navigationId, $request, $context);
            }

            $value = $this->cache->get($key, function (ItemInterface $item) use ($navigationId, $request, $context) {
                $name = self::buildName($navigationId);

                $response = $this->tracer->trace($name, fn () => $this->getDecorated()->load($navigationId, $request, $context));

                $item->tag($this->generateTags($navigationId, $response, $request, $context));

                return CacheValueCompressor::compress($response);
            });

            return CacheValueCompressor::uncompress($value);
        });
    }

    private function generateKey(string $navigationId, Request $request, ChannelContext $context): ?string
    {
        $parts = [...$request->query->all(), ...$request->request->all(), ...[$this->generator->getChannelContextHash($context, [RuleAreas::CATEGORY_AREA, RuleAreas::PRODUCT_AREA])]];

        $event = new CategoryRouteCacheKeyEvent($navigationId, $parts, $request, $context, null);
        $this->dispatcher->dispatch($event);

        if (!$event->shouldCache()) {
            return null;
        }

        return self::buildName($navigationId) . '-' . Hasher::hash($event->getParts());
    }

    /**
     * @return array<string>
     */
    private function generateTags(string $navigationId, CategoryRouteResponse $response, Request $request, ChannelContext $context): array
    {
        $tags = array_merge(
            $this->tracer->get(self::buildName($navigationId)),
            $this->extractProductIds($response),
            [self::buildName($navigationId)]
        );

        $event = new CategoryRouteCacheTagsEvent($navigationId, $tags, $request, $response, $context, null);
        $this->dispatcher->dispatch($event);

        return array_unique(array_filter($event->getTags()));
    }

    /**
     * @return array<string>
     */
    private function extractProductIds(CategoryRouteResponse $response): array
    {
        $page = $response->getCategory()->getCmsPage();

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