<?php declare(strict_types=1);

namespace Cicada\Core\Content\Seo;

use Doctrine\DBAL\Connection;
use Cicada\Core\Content\Seo\SeoUrlRoute\SeoUrlRouteRegistry;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Context\SystemSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\NandFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Language\LanguageCollection;
use Cicada\Core\System\Channel\ChannelCollection;

/**
 * This class can be used to regenerate the seo urls for a route and an offset at ids.
 */
#[Package('content')]
class SeoUrlUpdater
{
    /**
     * @internal
     *
     * @param EntityRepository<LanguageCollection> $languageRepository
     * @param EntityRepository<ChannelCollection> $channelRepository
     */
    public function __construct(
        private readonly EntityRepository $languageRepository,
        private readonly SeoUrlRouteRegistry $seoUrlRouteRegistry,
        private readonly SeoUrlGenerator $seoUrlGenerator,
        private readonly SeoUrlPersister $seoUrlPersister,
        private readonly Connection $connection,
        private readonly EntityRepository $channelRepository
    ) {
    }

    /**
     * @param array<string> $ids
     */
    public function update(string $routeName, array $ids): void
    {
        $templates = $routeName !== '' ? $this->loadUrlTemplate($routeName) : [];
        if (empty($templates)) {
            return;
        }

        $route = $this->seoUrlRouteRegistry->findByRouteName($routeName);
        if ($route === null) {
            throw new \RuntimeException(\sprintf('Route by name %s not found', $routeName));
        }

        $context = Context::createDefaultContext();

        $languageChains = $this->fetchLanguageChains($context);

        $criteria = new Criteria();
        $criteria->addFilter(new NandFilter([new EqualsFilter('typeId', Defaults::SALES_CHANNEL_TYPE_API)]));

        $channels = $this->channelRepository->search($criteria, $context)->getEntities();

        foreach ($templates as $config) {
            $template = $config['template'];
            $channel = $channels->get($config['channelId']);
            if ($template === '' || $channel === null) {
                continue;
            }

            $chain = $languageChains[$config['languageId']];
            $languageContext = new Context(new SystemSource(), [], Defaults::CURRENCY, $chain);
            $languageContext->setConsiderInheritance(true);

            // generate new seo urls
            $urls = $this->seoUrlGenerator->generate($ids, $template, $route, $languageContext, $channel);

            // persist seo urls to storage
            $this->seoUrlPersister->updateSeoUrls($languageContext, $routeName, $ids, $urls, $channel);
        }
    }

    /**
     * Loads the SEO url templates for the given $routeName for all combinations of languages and sales channels
     *
     * @param non-empty-string $routeName
     *
     * @return list<array{channelId: string, languageId: string, template: string}>
     */
    private function loadUrlTemplate(string $routeName): array
    {
        $query = 'SELECT DISTINCT
               LOWER(HEX(channel.id)) as channelId,
               LOWER(HEX(domains.language_id)) as languageId
             FROM channel_domain as domains
             INNER JOIN channel
               ON domains.channel_id = channel.id
               AND channel.active = 1';
        $parameters = [];

        $query .= ' AND channel.type_id != :apiTypeId';
        $parameters['apiTypeId'] = Uuid::fromHexToBytes(Defaults::SALES_CHANNEL_TYPE_API);

        $domains = $this->connection->fetchAllAssociative($query, $parameters);

        if ($domains === []) {
            return [];
        }

        $channelTemplates = $this->connection->fetchAllKeyValue(
            'SELECT LOWER(HEX(channel_id)) as channel_id, template
             FROM seo_url_template
             WHERE route_name LIKE :route',
            ['route' => $routeName]
        );

        if (!\array_key_exists('', $channelTemplates)) {
            throw new \RuntimeException('Default templates not configured');
        }

        $default = (string) $channelTemplates[''];

        $result = [];
        foreach ($domains as $domain) {
            $channelId = $domain['channelId'];

            $result[] = [
                'channelId' => $channelId,
                'languageId' => $domain['languageId'],
                'template' => $channelTemplates[$channelId] ?? $default,
            ];
        }

        return $result;
    }

    /**
     * @return array<string, array<string>>
     */
    private function fetchLanguageChains(Context $context): array
    {
        $languages = $this->languageRepository->search(new Criteria(), $context)->getEntities()->getElements();

        $languageChains = [];
        foreach ($languages as $language) {
            $languageId = $language->getId();
            $languageChains[$languageId] = array_filter([
                $languageId,
                $language->getParentId(),
                Defaults::LANGUAGE_SYSTEM,
            ]);
        }

        return $languageChains;
    }
}
