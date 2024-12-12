<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Doctrine\DBAL\Connection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1536232620ChannelType extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536232620;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE `channel_type`
            (
                `id`              binary(16) NOT NULL,
                `cover_url`       varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `icon_name`       varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `screenshot_urls` json                                                          DEFAULT NULL,
                `created_at`      datetime(3) NOT NULL,
                `updated_at`      datetime(3) DEFAULT NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `json.channel_type.screenshot_urls` CHECK (json_valid(`screenshot_urls`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
        $connection->executeStatement('
            CREATE TABLE `channel_type_translation` (
              `channel_type_id` binary(16) NOT NULL,
              `language_id` binary(16) NOT NULL,
              `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `manufacturer` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `description_long` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
              `custom_fields` json DEFAULT NULL,
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              PRIMARY KEY (`channel_type_id`,`language_id`),
              KEY `fk.channel_type_translation.language_id` (`language_id`),
              CONSTRAINT `fk.channel_type_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk.channel_type_translation.channel_type_id` FOREIGN KEY (`channel_type_id`) REFERENCES `channel_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `json.channel_type_translation.custom_fields` CHECK (json_valid(`custom_fields`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
