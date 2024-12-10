<?php
declare(strict_types=1);

namespace Cicada\Frontend\Theme\ConfigLoader;

use Doctrine\DBAL\Connection;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Uuid\Uuid;

#[Package('frontend')]
class DatabaseAvailableThemeProvider extends AbstractAvailableThemeProvider
{
    /**
     * @internal
     */
    public function __construct(private readonly Connection $connection)
    {
    }

    public function getDecorated(): AbstractAvailableThemeProvider
    {
        throw new DecorationPatternException(self::class);
    }

    public function load(Context $context, bool $activeOnly): array
    {
        $qb = $this->connection->createQueryBuilder()
            ->from('theme_channel')
            ->select(['LOWER(HEX(channel_id))', 'LOWER(HEX(theme_id))'])
            ->leftJoin('theme_channel', 'channel', 'channel', 'channel.id = theme_channel.channel_id')
            ->where('channel.type_id = :typeId')
            ->setParameter('typeId', Uuid::fromHexToBytes(Defaults::CHANNEL_TYPE_WEB));

        if ($activeOnly) {
            $qb->andWhere('channel.active = 1');
        }

        /** @var array<string, string> $keyValue */
        $keyValue = $qb->executeQuery()->fetchAllKeyValue();

        return $keyValue;
    }
}
