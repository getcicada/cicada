<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Doctrine\DBAL\Connection;
use CIcada\Core\Framework\Log\Package;
use CIcada\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1568120239CmsSection extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1568120239;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<'SQL'
            CREATE TABLE `cms_section`
            (
                `id`                    binary(16) NOT NULL,
                `cms_page_id`           binary(16) NOT NULL,
                `position`              int                                     NOT NULL,
                `type`                  varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
                `name`                  varchar(255) COLLATE utf8mb4_unicode_ci          DEFAULT NULL,
                `locked`                tinyint(1) NOT NULL DEFAULT '0',
                `sizing_mode`           varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'boxed',
                `mobile_behavior`       varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'wrap',
                `background_color`      varchar(255) COLLATE utf8mb4_unicode_ci          DEFAULT NULL,
                `background_media_id`   binary(16) DEFAULT NULL,
                `background_media_mode` varchar(255) COLLATE utf8mb4_unicode_ci          DEFAULT NULL,
                `visibility`            json                                             DEFAULT NULL,
                `css_class`             varchar(255) COLLATE utf8mb4_unicode_ci          DEFAULT NULL,
                `custom_fields`         json                                             DEFAULT NULL,
                `created_at`            datetime(3) NOT NULL,
                `updated_at`            datetime(3) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY                     `fk.cms_section.background_media_id` (`background_media_id`),
                KEY                     `fk.cms_section.cms_page_id` (`cms_page_id`),
                CONSTRAINT `fk.cms_section.background_media_id` FOREIGN KEY (`background_media_id`) REFERENCES `media` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk.cms_section.cms_page_id` FOREIGN KEY (`cms_page_id`) REFERENCES `cms_page` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `json.cms_section.custom_fields` CHECK (json_valid(`custom_fields`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
