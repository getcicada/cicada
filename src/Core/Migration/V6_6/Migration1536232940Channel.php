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
class Migration1536232940Channel extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536232940;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<'SQL'
            CREATE TABLE `channel`
            (
                `id`                       binary(16) NOT NULL,
                `type_id`                  binary(16) NOT NULL,
                `short_name`               varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `configuration`            json                                   DEFAULT NULL,
                `access_key`               varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                `navigation_category_id`    binary(16) NOT NULL,
                `navigation_category_version_id` BINARY(16) NOT NULL,
                 `navigation_category_depth` int NOT NULL DEFAULT '2',
                `footer_category_id` BINARY(16) NULL,
                `footer_category_version_id` BINARY(16) NULL,
                `service_category_id` BINARY(16) NULL,
                `service_category_version_id` BINARY(16) NULL,
                `language_id`              binary(16) NOT NULL,
                `active`                   tinyint(1) NOT NULL DEFAULT '1',
                `maintenance`              tinyint(1) NOT NULL DEFAULT '0',
                `maintenance_ip_whitelist` json                                   DEFAULT NULL,
                `created_at`               datetime(3) NOT NULL,
                `updated_at`               datetime(3) DEFAULT NULL,
                `home_cms_page_id`         binary(16) DEFAULT NULL,
                `home_cms_page_version_id` binary(16) DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq.access_key` (`access_key`),
                KEY                        `fk.channel.language_id` (`language_id`),
                KEY                        `fk.channel.type_id` (`type_id`),
                KEY `fk.channel.footer_category_id` (`footer_category_id`,`footer_category_version_id`),
                KEY `fk.channel.service_category_id` (`service_category_id`,`service_category_version_id`),
                KEY `fk.channel.navigation_category_id` (`navigation_category_id`,`navigation_category_version_id`),
                KEY `fk.channel.home_cms_page` (`home_cms_page_id`,`home_cms_page_version_id`),
                CONSTRAINT `fk.channel.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk.channel.type_id` FOREIGN KEY (`type_id`) REFERENCES `channel_type` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk.channel.navigation_category_id` FOREIGN KEY (`navigation_category_id`, `navigation_category_version_id`) REFERENCES `category` (`id`, `version_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                  CONSTRAINT `fk.channel.service_category_id` FOREIGN KEY (`service_category_id`, `service_category_version_id`) REFERENCES `category` (`id`, `version_id`) ON DELETE RESTRICT ON UPDATE CASCADE,

                  CONSTRAINT `fk.channel.footer_category_id` FOREIGN KEY (`footer_category_id`, `footer_category_version_id`) REFERENCES `category` (`id`, `version_id`) ON DELETE RESTRICT ON UPDATE CASCADE,

                CONSTRAINT `json.channel.configuration` CHECK (json_valid(`configuration`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($sql);

        $connection->executeStatement('
            CREATE TABLE `channel_translation` (
              `channel_id` binary(16) NOT NULL,
              `language_id` binary(16) NOT NULL,
              `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `home_keywords` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `home_meta_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `home_meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `home_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `home_enabled` tinyint NOT NULL DEFAULT "1",
              `home_slot_config` json DEFAULT NULL,
              `custom_fields` json DEFAULT NULL,
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              PRIMARY KEY (`channel_id`,`language_id`),
              KEY `fk.channel_translation.language_id` (`language_id`),
              CONSTRAINT `fk.channel_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk.channel_translation.channel_id` FOREIGN KEY (`channel_id`) REFERENCES `channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `json.channel_translation.custom_fields` CHECK (json_valid(`custom_fields`)),
              CONSTRAINT `json.channel_translation.home_slot_config` CHECK (json_valid(`home_slot_config`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $connection->executeStatement('
            CREATE TABLE `channel_language` (
              `channel_id` binary(16) NOT NULL,
              `language_id` binary(16) NOT NULL,
              PRIMARY KEY (`channel_id`,`language_id`),
              KEY `fk.channel_language.language_id` (`language_id`),
              CONSTRAINT `fk.channel_language.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk.channel_language.channel_id` FOREIGN KEY (`channel_id`) REFERENCES `channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
