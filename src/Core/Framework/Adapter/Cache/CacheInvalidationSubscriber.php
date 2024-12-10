<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Adapter\Cache;

use Cicada\Core\Content\Cms\CmsPageDefinition;
use Cicada\Core\Content\LandingPage\Channel\LandingPageRoute;
use Cicada\Core\Content\LandingPage\Event\LandingPageIndexerEvent;
use Cicada\Core\Framework\Adapter\Translation\Translator;
use Cicada\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGenerator;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Channel\Aggregate\ChannelLanguage\ChannelLanguageDefinition;
use Cicada\Core\System\Channel\ChannelDefinition;
use Cicada\Core\System\Channel\Context\CachedBaseContextFactory;
use Cicada\Core\System\Channel\Context\CachedChannelContextFactory;
use Cicada\Core\System\Language\Channel\LanguageRoute;
use Cicada\Core\System\Language\LanguageDefinition;
use Cicada\Core\System\Snippet\SnippetDefinition;
use Cicada\Core\System\SystemConfig\CachedSystemConfigLoader;
use Cicada\Core\System\SystemConfig\Event\SystemConfigChangedHook;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

#[Package('core')]
class CacheInvalidationSubscriber
{
    /**
     * @internal
     */
    public function __construct(
        private readonly CacheInvalidator $cacheInvalidator,
        private readonly Connection $connection,
        private readonly bool $fineGrainedCacheSnippet,
        private readonly bool $fineGrainedCacheConfig,
    ) {
    }

    public function invalidateConfig(): void
    {
        // invalidates the complete cached config
        $this->cacheInvalidator->invalidate([
            CachedSystemConfigLoader::CACHE_TAG,
        ]);
    }

    public function invalidateConfigKey(SystemConfigChangedHook $event): void
    {
        if (Feature::isActive('cache_rework')) {
            $this->cacheInvalidator->invalidate(['global.system.config', CachedSystemConfigLoader::CACHE_TAG]);

            return;
        }

        $keys = [];
        if ($this->fineGrainedCacheConfig) {
            /** @var list<string> $keys */
            $keys = array_map(
                static fn (string $key) => SystemConfigService::buildName($key),
                $event->getWebhookPayload()['changes']
            );
        } else {
            $keys[] = 'global.system.config';
        }

        // invalidates the complete cached config and routes which access a specific key
        $this->cacheInvalidator->invalidate([
            ...$keys,
            CachedSystemConfigLoader::CACHE_TAG,
        ]);
    }

    public function invalidateSnippets(EntityWrittenContainerEvent $event): void
    {
        // invalidates all http cache items where the snippets used
        $snippets = $event->getEventByEntityName(SnippetDefinition::ENTITY_NAME);

        if (!$snippets) {
            return;
        }

        if (Feature::isActive('cache_rework')) {
            $setIds = $this->getSetIds($snippets->getIds());

            if (empty($setIds)) {
                return;
            }

            $this->cacheInvalidator->invalidate(array_map(Translator::tag(...), $setIds));

            return;
        }

        if (!$this->fineGrainedCacheSnippet) {
            $this->cacheInvalidator->invalidate(['cicada.translator']);

            return;
        }

        $tags = [];
        foreach ($snippets->getPayloads() as $payload) {
            if (isset($payload['translationKey'])) {
                $tags[] = Translator::buildName($payload['translationKey']);
            }
        }
        $this->cacheInvalidator->invalidate($tags);
    }

    public function invalidateCmsPageIds(EntityWrittenContainerEvent $event): void
    {
        // invalidates all routes and http cache pages where a cms page was loaded, the id is assigned as tag
        /** @var list<string> $ids */
        $ids = array_map(EntityCacheKeyGenerator::buildCmsTag(...), $event->getPrimaryKeys(CmsPageDefinition::ENTITY_NAME));
        $this->cacheInvalidator->invalidate($ids);
    }
    public function invalidateIndexedLandingPages(LandingPageIndexerEvent $event): void
    {
        // invalidates the landing page route, if the corresponding landing page changed
        /** @var list<string> $ids */
        $ids = array_map(LandingPageRoute::buildName(...), $event->getIds());
        $this->cacheInvalidator->invalidate($ids);
    }

    public function invalidateLanguageRoute(EntityWrittenContainerEvent $event): void
    {
        // invalidates the language route when a language changed or an assignment between the sales channel and language changed
        $this->cacheInvalidator->invalidate([
            ...$this->getChangedLanguageAssignments($event),
            ...$this->getChangedLanguages($event),
        ]);
    }

    /**
     * @deprecated tag:v6.7.0 - reason:remove-subscriber - Will be removed, use invalidateProduct instead
     */
    public function invalidateSearch(): void
    {
        if (Feature::isActive('cache_rework')) {
            return;
        }
        // invalidates the search and suggest route each time a product changed
        $this->cacheInvalidator->invalidate([
            'product-suggest-route',
            'product-search-route',
        ]);
    }



    public function invalidateContext(EntityWrittenContainerEvent $event): void
    {
        // invalidates the context cache - each time one of the entities which are considered inside the context factory changed
        $ids = $event->getPrimaryKeys(ChannelDefinition::ENTITY_NAME);
        $keys = array_map(CachedChannelContextFactory::buildName(...), $ids);
        $keys = array_merge($keys, array_map(CachedBaseContextFactory::buildName(...), $ids));

        if ($event->getEventByEntityName(LanguageDefinition::ENTITY_NAME)) {
            $keys[] = CachedChannelContextFactory::ALL_TAG;
        }

        /** @var string[] $keys */
        $keys = array_filter(array_unique($keys));

        if (empty($keys)) {
            return;
        }

        $this->cacheInvalidator->invalidate($keys);
    }
    /**
     * @return list<string>
     */
    private function getChangedLanguages(EntityWrittenContainerEvent $event): array
    {
        $ids = $event->getPrimaryKeys(LanguageDefinition::ENTITY_NAME);
        if (empty($ids)) {
            return [];
        }

        // Used to detect changes to the language itself and invalidate the route for all sales channels in which the language is assigned.
        $ids = $this->connection->fetchFirstColumn(
            'SELECT DISTINCT LOWER(HEX(channel_id)) as id FROM channel_language WHERE language_id IN (:ids)',
            ['ids' => Uuid::fromHexToBytesList($ids)],
            ['ids' => ArrayParameterType::BINARY]
        );

        $tags = [];
        if ($event->getDeletedPrimaryKeys(LanguageDefinition::ENTITY_NAME)) {
            $tags[] = LanguageRoute::ALL_TAG;
        }

        return array_merge($tags, array_map(LanguageRoute::buildName(...), $ids));
    }

    /**
     * @return list<string>
     */
    private function getChangedLanguageAssignments(EntityWrittenContainerEvent $event): array
    {
        // Used to detect changes to the language assignment of a sales channel
        $ids = $event->getPrimaryKeys(ChannelLanguageDefinition::ENTITY_NAME);

        $ids = array_column($ids, 'channelId');

        return array_map(LanguageRoute::buildName(...), $ids);
    }

    /**
     * @param array<string> $ids
     *
     * @return array<string>
     */
    private function getSetIds(array $ids): array
    {
        return $this->connection->fetchFirstColumn(
            'SELECT DISTINCT LOWER(HEX(snippet_set_id)) FROM snippet WHERE id IN (:ids)',
            ['ids' => Uuid::fromHexToBytesList($ids)],
            ['ids' => ArrayParameterType::BINARY]
        );
    }
}
