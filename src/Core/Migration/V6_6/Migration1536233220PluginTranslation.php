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
class Migration1536233220PluginTranslation extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536233220;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE `plugin_translation` (
              `plugin_id` binary(16) NOT NULL,
              `language_id` binary(16) NOT NULL,
              `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
              `manufacturer_link` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
              `support_link` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
              `custom_fields` json DEFAULT NULL,
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              PRIMARY KEY (`plugin_id`,`language_id`),
              KEY `fk.plugin_translation.language_id` (`language_id`),
              CONSTRAINT `fk.plugin_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk.plugin_translation.plugin_id` FOREIGN KEY (`plugin_id`) REFERENCES `plugin` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `json.plugin_translation.custom_fields` CHECK (json_valid(`custom_fields`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
