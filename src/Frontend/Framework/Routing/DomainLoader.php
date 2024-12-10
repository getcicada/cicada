<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Routing;

use Doctrine\DBAL\Connection;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\Doctrine\FetchModeHelper;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;

/**
 * @phpstan-import-type Domain from AbstractDomainLoader
 */
#[Package('frontend')]
class DomainLoader extends AbstractDomainLoader
{
    /**
     * @internal
     */
    public function __construct(private readonly Connection $connection)
    {
    }

    public function getDecorated(): AbstractDomainLoader
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @return array<string, Domain>
     */
    public function load(): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->select(
            'CONCAT(TRIM(TRAILING \'/\' FROM domain.url), \'/\') `key`',
            'CONCAT(TRIM(TRAILING \'/\' FROM domain.url), \'/\') url',
            'LOWER(HEX(domain.id)) id',
            'LOWER(HEX(channel.id)) channelId',
            'LOWER(HEX(channel.type_id)) typeId',
            'LOWER(HEX(domain.snippet_set_id)) snippetSetId',
            'LOWER(HEX(domain.currency_id)) currencyId',
            'LOWER(HEX(domain.language_id)) languageId',
            'LOWER(HEX(theme.id)) themeId',
            'channel.maintenance maintenance',
            'channel.maintenance_ip_whitelist maintenanceIpWhitelist',
            'snippet_set.iso as locale',
            'theme.technical_name as themeName',
            'parentTheme.technical_name as parentThemeName',
        );

        $query->from('channel');
        $query->innerJoin('channel', 'channel_domain', 'domain', 'domain.channel_id = channel.id');
        $query->innerJoin('domain', 'snippet_set', 'snippet_set', 'snippet_set.id = domain.snippet_set_id');
        $query->leftJoin('channel', 'theme_channel', 'theme_channel', 'channel.id = theme_channel.channel_id');
        $query->leftJoin('theme_channel', 'theme', 'theme', 'theme_channel.theme_id = theme.id');
        $query->leftJoin('theme', 'theme', 'parentTheme', 'theme.parent_theme_id = parentTheme.id');
        $query->where('channel.type_id = UNHEX(:typeId)');
        $query->andWhere('channel.active');
        $query->setParameter('typeId', Defaults::CHANNEL_TYPE_STOREFRONT);

        /** @var array<string, Domain> $domains */
        $domains = FetchModeHelper::groupUnique($query->executeQuery()->fetchAllAssociative());

        return $domains;
    }
}
