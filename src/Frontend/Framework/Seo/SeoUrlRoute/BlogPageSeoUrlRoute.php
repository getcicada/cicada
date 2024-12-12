<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Seo\SeoUrlRoute;

use Cicada\Core\Content\Blog\BlogDefinition;
use Cicada\Core\Content\Blog\BlogEntity;
use Cicada\Core\Content\Seo\SeoUrlRoute\SeoUrlMapping;
use Cicada\Core\Content\Seo\SeoUrlRoute\SeoUrlRouteConfig;
use Cicada\Core\Content\Seo\SeoUrlRoute\SeoUrlRouteInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\DataAbstractionLayer\PartialEntity;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelEntity;

#[Package('content')]
class BlogPageSeoUrlRoute implements SeoUrlRouteInterface
{
    final public const ROUTE_NAME = 'frontend.detail.page';
    final public const DEFAULT_TEMPLATE = '{{ blog.translated.name }}/{{ blog.blogNumber }}';

    /**
     * @internal
     */
    public function __construct(private readonly BlogDefinition $blogDefinition)
    {
    }

    public function getConfig(): SeoUrlRouteConfig
    {
        return new SeoUrlRouteConfig(
            $this->blogDefinition,
            self::ROUTE_NAME,
            self::DEFAULT_TEMPLATE,
            true
        );
    }

    public function prepareCriteria(Criteria $criteria, ChannelEntity $channel): void
    {
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new EqualsFilter('visibilities.channelId', $channel->getId()));
    }

    public function getMapping(Entity $blog, ?ChannelEntity $channel): SeoUrlMapping
    {
        if (!$blog instanceof BlogEntity && !$blog instanceof PartialEntity) {
            throw new \InvalidArgumentException('Expected BlogEntity');
        }

        $categories = $blog->get('mainCategories') ?? null;
        if ($categories instanceof EntityCollection && $channel !== null) {
            $filtered = $categories->filter(
                fn (Entity $category) => $category->get('channelId') === $channel->getId()
            );

            $blog->assign(['mainCategories' => $filtered]);
        }

        $blogJson = $blog->jsonSerialize();

        return new SeoUrlMapping(
            $blog,
            ['blogId' => $blog->getId()],
            [
                'blog' => $blogJson,
            ]
        );
    }
}
