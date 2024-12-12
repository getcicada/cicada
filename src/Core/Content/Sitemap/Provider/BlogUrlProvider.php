<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\Provider;

use Cicada\Core\Content\Blog\Aggregate\BlogVisibility\BlogVisibilityDefinition;
use Cicada\Core\Content\Blog\BlogDefinition;
use Cicada\Core\Content\Blog\BlogEntity;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Cicada\Core\Content\Sitemap\Service\ConfigHandler;
use Cicada\Core\Content\Sitemap\Struct\Url;
use Cicada\Core\Content\Sitemap\Struct\UrlResult;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\Common\IteratorFactory;
use Cicada\Core\Framework\DataAbstractionLayer\Doctrine\FetchModeHelper;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Routing\RouterInterface;

#[Package('services-settings')]
class BlogUrlProvider extends AbstractUrlProvider
{
    final public const CHANGE_FREQ = 'hourly';

    private const CONFIG_EXCLUDE_LINKED_BLOGS = 'core.sitemap.excludeLinkedBlogs';

    private const CONFIG_HIDE_AFTER_CLOSEOUT = 'core.listing.hideCloseoutBlogsWhenOutOfStock';

    /**
     * @internal
     */
    public function __construct(
        private readonly ConfigHandler       $configHandler,
        private readonly Connection          $connection,
        private readonly BlogDefinition      $definition,
        private readonly IteratorFactory     $iteratorFactory,
        private readonly RouterInterface     $router,
        private readonly SystemConfigService $systemConfigService
    ) {
    }

    public function getDecorated(): AbstractUrlProvider
    {
        throw new DecorationPatternException(self::class);
    }

    public function getName(): string
    {
        return 'blog';
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function getUrls(ChannelContext $context, int $limit, ?int $offset = null): UrlResult
    {
        $blogs = $this->getBlogs($context, $limit, $offset);

        if (empty($blogs)) {
            return new UrlResult([], null);
        }

        $keys = FetchModeHelper::keyPair($blogs);

        $seoUrls = $this->getSeoUrls(array_values($keys), 'frontend.detail.page', $context, $this->connection);

        /** @var array<string, array{seo_path_info: string}> $seoUrls */
        $seoUrls = FetchModeHelper::groupUnique($seoUrls);

        $urls = [];
        $url = new Url();

        foreach ($blogs as $blog) {
            $lastMod = $blog['updated_at'] ?: $blog['created_at'];

            $lastMod = (new \DateTime($lastMod))->format(Defaults::STORAGE_DATE_TIME_FORMAT);

            $newUrl = clone $url;

            if (isset($seoUrls[$blog['id']])) {
                $newUrl->setLoc($seoUrls[$blog['id']]['seo_path_info']);
            } else {
                $newUrl->setLoc($this->router->generate('frontend.detail.page', ['blogId' => $blog['id']]));
            }

            $newUrl->setLastmod(new \DateTime($lastMod));
            $newUrl->setChangefreq(self::CHANGE_FREQ);
            $newUrl->setResource(BlogDefinition::class);
            $newUrl->setIdentifier($blog['id']);

            $urls[] = $newUrl;
        }

        $keys = array_keys($keys);
        /** @var int|null $nextOffset */
        $nextOffset = array_pop($keys);

        return new UrlResult($urls, $nextOffset);
    }

    /**
     * @return list<array{id: string, created_at: string, updated_at: string}>
     */
    private function getBlogs(ChannelContext $context, int $limit, ?int $offset): array
    {
        $lastId = null;
        if ($offset) {
            $lastId = ['offset' => $offset];
        }

        $iterator = $this->iteratorFactory->createIterator($this->definition, $lastId);
        $query = $iterator->getQuery();
        $query->setMaxResults($limit);

        $showAfterCloseout = !$this->systemConfigService->get(self::CONFIG_HIDE_AFTER_CLOSEOUT, $context->getChannelId());

        $query->addSelect([
            '`blog`.created_at as created_at',
            '`blog`.updated_at as updated_at',
        ]);

        $query->leftJoin('`blog`', '`blog`', 'parent', '`blog`.parent_id = parent.id');
        $query->innerJoin('`blog`', 'blog_visibility', 'visibilities', 'blog.visibilities = visibilities.blog_id');

        $query->andWhere('`blog`.version_id = :versionId');

        if ($showAfterCloseout) {
            $query->andWhere('(`blog`.available = 1 OR `blog`.is_closeout)');
        } else {
            $query->andWhere('`blog`.available = 1');
        }

        $query->andWhere('IFNULL(`blog`.active, parent.active) = 1');
        $query->andWhere('(`blog`.child_count = 0 OR `blog`.parent_id IS NOT NULL)');
        $query->andWhere('(`blog`.parent_id IS NULL OR parent.canonical_blog_id IS NULL OR parent.canonical_blog_id = `blog`.id)');
        $query->andWhere('visibilities.blog_version_id = :versionId');
        $query->andWhere('visibilities.channel_id = :channelId');

        $excludedBlogIds = $this->getExcludedBlogIds($context);
        if (!empty($excludedBlogIds)) {
            $query->andWhere('`blog`.id NOT IN (:blogIds)');
            $query->setParameter('blogIds', Uuid::fromHexToBytesList($excludedBlogIds), ArrayParameterType::BINARY);
        }

        $excludeLinkedBlogs = $this->systemConfigService->getBool(self::CONFIG_EXCLUDE_LINKED_BLOGS, $context->getChannelId());
        if ($excludeLinkedBlogs) {
            $query->andWhere('visibilities.visibility != :excludedVisibility');
            $query->setParameter('excludedVisibility', BlogVisibilityDefinition::VISIBILITY_LINK);
        }

        $query->setParameter('versionId', Uuid::fromHexToBytes(Defaults::LIVE_VERSION));
        $query->setParameter('channelId', Uuid::fromHexToBytes($context->getChannelId()));

        /** @var list<array{id: string, created_at: string, updated_at: string}> $result */
        $result = $query->executeQuery()->fetchAllAssociative();

        return $result;
    }

    /**
     * @return array<string>
     */
    private function getExcludedBlogIds(ChannelContext $channelContext): array
    {
        $channelId = $channelContext->getChannel()->getId();

        $excludedUrls = $this->configHandler->get(ConfigHandler::EXCLUDED_URLS_KEY);
        if (empty($excludedUrls)) {
            return [];
        }

        $excludedUrls = array_filter($excludedUrls, static function (array $excludedUrl) use ($channelId) {
            if ($excludedUrl['resource'] !== BlogEntity::class) {
                return false;
            }

            if ($excludedUrl['channelId'] !== $channelId) {
                return false;
            }

            return true;
        });

        return array_column($excludedUrls, 'identifier');
    }
}
