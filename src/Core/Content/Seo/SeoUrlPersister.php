<?php declare(strict_types=1);

namespace Cicada\Core\Content\Seo;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Cicada\Core\Content\Seo\Event\SeoUrlUpdateEvent;
use Cicada\Core\Content\Seo\SeoUrl\SeoUrlEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Doctrine\MultiInsertQueryQueue;
use Cicada\Core\Framework\DataAbstractionLayer\Doctrine\RetryableQuery;
use Cicada\Core\Framework\DataAbstractionLayer\Doctrine\RetryableTransaction;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Channel\ChannelEntity;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[Package('content')]
class SeoUrlPersister
{
    /**
     * @internal
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly EntityRepository $seoUrlRepository,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @param array<string> $foreignKeys
     * @param iterable<array<string, mixed>|SeoUrlEntity> $seoUrls
     */
    public function updateSeoUrls(Context $context, string $routeName, array $foreignKeys, iterable $seoUrls, ChannelEntity $channel): void
    {
        $languageId = $context->getLanguageId();
        $canonicals = $this->findCanonicalPaths($routeName, $languageId, $foreignKeys);
        $dateTime = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);
        $insertQuery = new MultiInsertQueryQueue($this->connection, 250, false, true);

        $updatedFks = [];
        $obsoleted = [];

        $processed = [];

        $channelId = $channel->getId();
        $updates = [];
        foreach ($seoUrls as $seoUrl) {
            if ($seoUrl instanceof SeoUrlEntity) {
                $seoUrl = $seoUrl->jsonSerialize();
            }
            $updates[] = $seoUrl;

            $fk = $seoUrl['foreignKey'];
            $channelId = $seoUrl['channelId'] ??= null;

            // skip duplicates
            if (isset($processed[$fk][$channelId])) {
                continue;
            }

            if (!isset($processed[$fk])) {
                $processed[$fk] = [];
            }
            $processed[$fk][$channelId] = true;

            $updatedFks[] = $fk;

            if (!empty($seoUrl['error'])) {
                continue;
            }
            $existing = $canonicals[$fk][$channelId] ?? null;

            if ($existing) {
                // entity has override or does not change
                /** @phpstan-ignore-next-line PHPStan could not recognize the array generated from the jsonSerialize method of the SeoUrlEntity */
                if ($this->skipUpdate($existing, $seoUrl)) {
                    continue;
                }
                $obsoleted[] = $existing['id'];
            }

            $insert = [];
            $insert['id'] = Uuid::randomBytes();

            if ($channelId) {
                $insert['channel_id'] = Uuid::fromHexToBytes($channelId);
            }
            $insert['language_id'] = Uuid::fromHexToBytes($languageId);
            $insert['foreign_key'] = Uuid::fromHexToBytes($fk);

            $insert['path_info'] = $seoUrl['pathInfo'];
            $insert['seo_path_info'] = ltrim((string) $seoUrl['seoPathInfo'], '/');

            $insert['route_name'] = $routeName;
            $insert['is_canonical'] = ($seoUrl['isCanonical'] ?? true) ? 1 : null;
            $insert['is_modified'] = ($seoUrl['isModified'] ?? false) ? 1 : 0;
            $insert['is_deleted'] = ($seoUrl['isDeleted'] ?? true) ? 1 : 0;

            $insert['created_at'] = $dateTime;

            $insertQuery->addInsert($this->seoUrlRepository->getDefinition()->getEntityName(), $insert);
        }

        RetryableTransaction::retryable($this->connection, function () use ($obsoleted, $insertQuery, $foreignKeys, $updatedFks, $channelId): void {
            $this->obsoleteIds($obsoleted, $channelId);
            $insertQuery->execute();

            $deletedIds = array_diff($foreignKeys, $updatedFks);
            $notDeletedIds = array_unique(array_intersect($foreignKeys, $updatedFks));

            $this->markAsDeleted(true, $deletedIds, $channelId);
            $this->markAsDeleted(false, $notDeletedIds, $channelId);
        });

        $this->eventDispatcher->dispatch(new SeoUrlUpdateEvent($updates));
    }

    /**
     * @param array{isModified: bool, seoPathInfo: string, channelId: string} $existing
     * @param array{isModified?: bool, seoPathInfo: string, channelId: string} $seoUrl
     */
    private function skipUpdate(array $existing, array $seoUrl): bool
    {
        if ($existing['isModified'] && !($seoUrl['isModified'] ?? false) && trim($seoUrl['seoPathInfo']) !== '') {
            return true;
        }

        return $seoUrl['seoPathInfo'] === $existing['seoPathInfo']
            && $seoUrl['channelId'] === $existing['channelId'];
    }

    /**
     * @param array<string> $foreignKeys
     *
     * @return array<string, mixed>
     */
    private function findCanonicalPaths(string $routeName, string $languageId, array $foreignKeys): array
    {
        $fks = Uuid::fromHexToBytesList($foreignKeys);
        $languageId = Uuid::fromHexToBytes($languageId);

        $query = $this->connection->createQueryBuilder();
        $query->select(
            'LOWER(HEX(seo_url.id)) as id',
            'LOWER(HEX(seo_url.foreign_key)) foreignKey',
            'LOWER(HEX(seo_url.channel_id)) channelId',
            'seo_url.is_modified as isModified',
            'seo_url.seo_path_info seoPathInfo',
        );
        $query->from('seo_url', 'seo_url');

        $query->andWhere('seo_url.route_name = :routeName');
        $query->andWhere('seo_url.language_id = :language_id');
        $query->andWhere('seo_url.is_canonical = 1');
        $query->andWhere('seo_url.foreign_key IN (:foreign_keys)');

        $query->setParameter('routeName', $routeName);
        $query->setParameter('language_id', $languageId);
        $query->setParameter('foreign_keys', $fks, ArrayParameterType::BINARY);

        $rows = $query->executeQuery()->fetchAllAssociative();

        $canonicals = [];
        foreach ($rows as $row) {
            $row['isModified'] = (bool) $row['isModified'];
            $foreignKey = (string) $row['foreignKey'];
            if (!isset($canonicals[$foreignKey])) {
                $canonicals[$foreignKey] = [$row['channelId'] => $row];

                continue;
            }
            $canonicals[$foreignKey][$row['channelId']] = $row;
        }

        return $canonicals;
    }

    /**
     * @param list<string> $ids
     */
    private function obsoleteIds(array $ids, ?string $channelId): void
    {
        if (empty($ids)) {
            return;
        }

        $ids = Uuid::fromHexToBytesList($ids);

        $query = $this->connection->createQueryBuilder()
            ->update('seo_url')
            ->set('is_canonical', 'NULL')
            ->where('id IN (:ids)')
            ->setParameter('ids', $ids, ArrayParameterType::BINARY);

        if ($channelId) {
            $query->andWhere('channel_id = :channelId');
            $query->setParameter('channelId', Uuid::fromHexToBytes($channelId));
        }

        RetryableQuery::retryable($this->connection, function () use ($query): void {
            $query->executeStatement();
        });
    }

    /**
     * @param array<string> $ids
     */
    private function markAsDeleted(bool $deleted, array $ids, ?string $channelId): void
    {
        if (empty($ids)) {
            return;
        }

        $ids = Uuid::fromHexToBytesList($ids);
        $query = $this->connection->createQueryBuilder()
            ->update('seo_url')
            ->set('is_deleted', $deleted ? '1' : '0')
            ->where('foreign_key IN (:fks)')
            ->setParameter('fks', $ids, ArrayParameterType::BINARY);

        if ($channelId) {
            $query->andWhere('channel_id = :channelId');
            $query->setParameter('channelId', Uuid::fromHexToBytes($channelId));
        }

        $query->executeStatement();
    }
}
