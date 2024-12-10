<?php declare(strict_types=1);

namespace Cicada\Core\Content\Seo;

use Doctrine\DBAL\Connection;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\QueryBuilder;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Uuid\Uuid;

/**
 * @phpstan-import-type ResolvedSeoUrl from AbstractSeoResolver
 */
#[Package('content')]
class SeoResolver extends AbstractSeoResolver
{
    /**
     * @internal
     */
    public function __construct(private readonly Connection $connection)
    {
    }

    public function getDecorated(): AbstractSeoResolver
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @return ResolvedSeoUrl
     */
    public function resolve(string $languageId, string $channelId, string $pathInfo): array
    {
        $seoPathInfo = trim($pathInfo, '/');

        $query = (new QueryBuilder($this->connection))
            ->select('id', 'path_info pathInfo', 'is_canonical isCanonical', 'channel_id channelId')
            ->from('seo_url')
            ->where('language_id = :language_id')
            ->andWhere('(channel_id = :channel_id OR channel_id IS NULL)')
            ->andWhere('(seo_path_info = :seoPath OR seo_path_info = :seoPathWithSlash)')
            ->setParameter('language_id', Uuid::fromHexToBytes($languageId))
            ->setParameter('channel_id', Uuid::fromHexToBytes($channelId))
            ->setParameter('seoPath', $seoPathInfo)
            ->setParameter('seoPathWithSlash', $seoPathInfo . '/');

        $query->setTitle('seo-url::resolve');

        $seoPaths = $query->executeQuery()->fetchAllAssociative();

        // sort seoPaths by filled channelId and isCanonical, save file sort on SQL server
        usort($seoPaths, static function ($a, $b) {
            if ($a['isCanonical'] === null) {
                return 1;
            }
            if ($b['isCanonical'] === null) {
                return -1;
            }

            if ($a['channelId'] === null) {
                return 1;
            }
            if ($b['channelId'] === null) {
                return -1;
            }

            return 0;
        });

        $seoPath = $seoPaths[0] ?? ['pathInfo' => $seoPathInfo, 'isCanonical' => false];

        if (!$seoPath['isCanonical']) {
            $query = (new QueryBuilder($this->connection))
                ->select('path_info pathInfo', 'seo_path_info seoPathInfo')
                ->from('seo_url')
                ->where('language_id = :language_id')
                ->andWhere('channel_id = :channel_id')
                ->andWhere('path_info = :pathInfo')
                ->andWhere('is_canonical = 1')
                ->setMaxResults(1)
                ->setParameter('language_id', Uuid::fromHexToBytes($languageId))
                ->setParameter('channel_id', Uuid::fromHexToBytes($channelId))
                ->setParameter('pathInfo', '/' . ltrim((string) $seoPath['pathInfo'], '/'));

            $query->setTitle('seo-url::resolve-fallback');

            // we only have an id when the hit seo url was not a canonical url, save the one filter condition
            if (isset($seoPath['id'])) {
                $query->andWhere('id != :id')
                    ->setParameter('id', $seoPath['id']);
            }

            $canonicalQueryResult = $query->executeQuery()->fetchAssociative();
            if ($canonicalQueryResult) {
                $seoPath['canonicalPathInfo'] = '/' . ltrim((string) $canonicalQueryResult['seoPathInfo'], '/');
            }
        }

        $seoPath['pathInfo'] = '/' . ltrim((string) $seoPath['pathInfo'], '/');

        return $seoPath;
    }
}
