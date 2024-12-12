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
class Migration1536233120Blog extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536233120;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE `blog` (
              `id` binary(16) NOT NULL,
              `version_id` binary(16) NOT NULL,
              `auto_increment` int NOT NULL AUTO_INCREMENT,
              `active` tinyint unsigned DEFAULT NULL,
              `parent_id` binary(16) DEFAULT NULL,
              `parent_version_id` binary(16) DEFAULT NULL,
              `blog_media_id` binary(16) DEFAULT NULL,
              `blog_media_version_id` binary(16) DEFAULT NULL,
              `category_tree` json DEFAULT NULL,
              `category_ids` json DEFAULT NULL,
              `media` binary(16) DEFAULT NULL,
              `prices` binary(16) DEFAULT NULL,
              `visibilities` binary(16) DEFAULT NULL,
              `categories` binary(16) DEFAULT NULL,
              `translations` binary(16) DEFAULT NULL,
              `price` json DEFAULT NULL,
              `created_at` datetime(3) DEFAULT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              `child_count` int DEFAULT NULL,
              `cmsPage` binary(16) DEFAULT NULL,
              `states` json DEFAULT NULL,
              `tag_ids` json DEFAULT NULL,
              `tags` binary(16) DEFAULT NULL,
              PRIMARY KEY (`id`,`version_id`),
              UNIQUE KEY `auto_increment` (`auto_increment`),
              KEY `fk.blog.parent_id` (`parent_id`,`parent_version_id`),
              KEY `fk.blog.blog_media_id` (`blog_media_id`,`blog_media_version_id`),
              CONSTRAINT `fk.blog.parent_id` FOREIGN KEY (`parent_id`, `parent_version_id`) REFERENCES `blog` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `json.blog.category_tree` CHECK (json_valid(`category_tree`)),
              CONSTRAINT `json.blog.states` CHECK (json_valid(`states`)),
              CONSTRAINT `json.blog.tag_ids` CHECK (json_valid(`tag_ids`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $connection->executeStatement('
            CREATE TABLE `blog_translation` (
              `blog_id` BINARY(16) NOT NULL,
              `blog_version_id` BINARY(16) NOT NULL,
              `language_id` BINARY(16) NOT NULL,
              `additional_text` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL,
              `name` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL,
              `keywords` MEDIUMTEXT COLLATE utf8mb4_unicode_ci NULL,
              `description` MEDIUMTEXT COLLATE utf8mb4_unicode_ci NULL,
              `meta_title` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL,
              `pack_unit` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL,
              `custom_fields` JSON NULL,
              `created_at` DATETIME(3) NOT NULL,
              `updated_at` DATETIME(3) NULL,
              PRIMARY KEY (`blog_id`, `blog_version_id`, `language_id`),
              CONSTRAINT `json.blog_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
              CONSTRAINT `fk.blog_translation.language_id` FOREIGN KEY (`language_id`)
                REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk.blog_translation.blog_id` FOREIGN KEY (`blog_id`, `blog_version_id`)
                REFERENCES `blog` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
