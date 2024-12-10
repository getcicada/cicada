<?php declare(strict_types=1);

namespace Cicada\Frontend\Migration\V6_6;

use Doctrine\DBAL\Connection;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Cicada\Core\Framework\Uuid\Uuid;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1552899789Theme extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1552899789;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE `theme` (
              `id` binary(16) NOT NULL,
              `technical_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
              `author` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
              `preview_media_id` binary(16) DEFAULT NULL,
              `parent_theme_id` binary(16) DEFAULT NULL,
              `base_config` json DEFAULT NULL,
              `config_values` json DEFAULT NULL,
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              `active` tinyint(1) DEFAULT "1",
              `theme_json` json DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uniq.theme.technical_name` (`technical_name`),
              KEY `fk.theme.preview_media_id` (`preview_media_id`),
              CONSTRAINT `theme_ibfk_1` FOREIGN KEY (`preview_media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
              CONSTRAINT `json.theme.base_config` CHECK (json_valid(`base_config`)),
              CONSTRAINT `json.theme.config_values` CHECK (json_valid(`config_values`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $connection->executeStatement('
            CREATE TABLE `theme_translation` (
              `theme_id` binary(16) NOT NULL,
              `language_id` binary(16) NOT NULL,
              `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
              `labels` json DEFAULT NULL,
              `custom_fields` json DEFAULT NULL,
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              `help_texts` json DEFAULT NULL,
              PRIMARY KEY (`theme_id`,`language_id`),
              KEY `fk.theme_translation.language_id` (`language_id`),
              CONSTRAINT `fk.theme_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk.theme_translation.theme_id` FOREIGN KEY (`theme_id`) REFERENCES `theme` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `json.theme_translation.custom_fields` CHECK (json_valid(`custom_fields`)),
              CONSTRAINT `json.theme_translation.labels` CHECK (json_valid(`labels`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $connection->executeStatement('
            CREATE TABLE `theme_channel` (
              `theme_id` binary(16) NOT NULL,
              `channel_id` binary(16) NOT NULL,
              PRIMARY KEY (`channel_id`),
              KEY `fk.theme_channel.theme_id` (`theme_id`),
              CONSTRAINT `fk.theme_channel.channel_id` FOREIGN KEY (`channel_id`) REFERENCES `channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk.theme_channel.theme_id` FOREIGN KEY (`theme_id`) REFERENCES `theme` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $defaultFolderId = Uuid::randomBytes();
        $connection->insert('media_default_folder', [
            'id' => $defaultFolderId,
            'entity' => 'theme',
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_FORMAT),
        ]);

        $mediaFolderConfigurationId = Uuid::randomBytes();
        $connection->insert('media_folder_configuration', [
            'id' => $mediaFolderConfigurationId,
            'no_association' => true,
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_FORMAT),
        ]);

        $connection->insert('media_folder', [
            'id' => Uuid::randomBytes(),
            'default_folder_id' => $defaultFolderId,
            'media_folder_configuration_id' => $mediaFolderConfigurationId,
            'name' => 'Theme Media',
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_FORMAT),
        ]);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
