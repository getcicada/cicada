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
class Migration1536232880Category extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536232880;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE `category` (
              `id` binary(16) NOT NULL,
              `version_id` binary(16) NOT NULL,
              `auto_increment` int NOT NULL AUTO_INCREMENT,
              `parent_id` binary(16) DEFAULT NULL,
              `parent_version_id` binary(16) DEFAULT NULL,
              `media_id` binary(16) DEFAULT NULL,
              `assignment_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT "blog",
              `path` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
              `after_category_id` binary(16) DEFAULT NULL,
              `after_category_version_id` binary(16) DEFAULT NULL,
              `display_nested_products` TINYINT(1) unsigned NOT NULL DEFAULT 1,
              `level` int unsigned NOT NULL DEFAULT "1",
              `active` tinyint(1) NOT NULL DEFAULT "1",
              `child_count` int unsigned NOT NULL DEFAULT "0",
              `visible` tinyint unsigned NOT NULL DEFAULT "1",
              `type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              PRIMARY KEY (`id`,`version_id`),
              UNIQUE KEY `auto_increment` (`auto_increment`),
              KEY `idx.level` (`level`),
              KEY `fk.category.media_id` (`media_id`),
              KEY `fk.category.parent_id` (`parent_id`,`parent_version_id`),
              KEY `fk.category.after_category_id` (`after_category_id`,`after_category_version_id`),
              CONSTRAINT `fk.category.after_category_id` FOREIGN KEY (`after_category_id`, `after_category_version_id`) REFERENCES `category` (`id`, `version_id`) ON DELETE SET NULL ON UPDATE CASCADE,
              CONSTRAINT `fk.category.media_id` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
              CONSTRAINT `fk.category.parent_id` FOREIGN KEY (`parent_id`, `parent_version_id`) REFERENCES `category` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $connection->executeStatement('
              CREATE TABLE `category_translation` (
              `category_id` binary(16) NOT NULL,
              `category_version_id` binary(16) NOT NULL,
              `language_id` binary(16) NOT NULL,
              `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `breadcrumb` json DEFAULT NULL,
              `internal_link` binary(16) DEFAULT NULL,
              `link_new_tab` tinyint DEFAULT NULL,
              `link_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `external_link` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
              `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
              `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `meta_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `keywords` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `custom_fields` json DEFAULT NULL,
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              `slot_config` json DEFAULT NULL,
              PRIMARY KEY (`category_id`,`category_version_id`,`language_id`),
              KEY `fk.category_translation.language_id` (`language_id`),
              CONSTRAINT `fk.category_translation.category_id` FOREIGN KEY (`category_id`, `category_version_id`) REFERENCES `category` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk.category_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `json.category_translation.custom_fields` CHECK (json_valid(`custom_fields`)),
              CONSTRAINT `json.category_translation.slot_config` CHECK (json_valid(`slot_config`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
