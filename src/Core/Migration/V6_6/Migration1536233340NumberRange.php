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
class Migration1536233340NumberRange extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536233340;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<'SQL'
            CREATE TABLE `number_range` (
              `id` binary(16) NOT NULL,
              `type_id` binary(16) NOT NULL,
              `global` tinyint(1) NOT NULL,
              `pattern` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
              `start` int NOT NULL,
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($sql);

        $sql = <<<'SQL'
            CREATE TABLE `number_range_translation` (
              `number_range_id` binary(16) NOT NULL,
              `name` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `custom_fields` json DEFAULT NULL,
              `language_id` binary(16) NOT NULL,
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              PRIMARY KEY (`number_range_id`,`language_id`),
              KEY `fk.number_range_translation.language_id` (`language_id`),
              CONSTRAINT `fk.number_range_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk.number_range_translation.number_range_id` FOREIGN KEY (`number_range_id`) REFERENCES `number_range` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `json.number_range_translation.custom_fields` CHECK (json_valid(`custom_fields`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($sql);

        $sql = <<<'SQL'
            CREATE TABLE `number_range_state` (
              `id` binary(16) NOT NULL,
              `number_range_id` binary(16) NOT NULL,
              `last_value` int NOT NULL,
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              PRIMARY KEY (`number_range_id`),
              UNIQUE KEY `uniq.id` (`id`),
              KEY `idx.number_range_id` (`number_range_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        // No Foreign Key here is intended. It should be possible to handle the state with another Persistence so
        // we can force MySQL to expect a Dependency here
        $connection->executeStatement($sql);

        $sql = <<<'SQL'
            CREATE TABLE `number_range_type` (
              `id` binary(16) NOT NULL,
              `technical_name` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `global` tinyint(1) NOT NULL,
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uniq.technical_name` (`technical_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($sql);

        $sql = <<<'SQL'
            CREATE TABLE `number_range_type_translation` (
              `number_range_type_id` binary(16) NOT NULL,
              `language_id` binary(16) NOT NULL,
              `type_name` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `custom_fields` json DEFAULT NULL,
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              PRIMARY KEY (`number_range_type_id`,`language_id`),
              KEY `fk.number_range_type_translation.language_id` (`language_id`),
              CONSTRAINT `fk.number_range_type_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk.number_range_type_translation.number_range_type_id` FOREIGN KEY (`number_range_type_id`) REFERENCES `number_range_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `json.number_range_type_translation.custom_fields` CHECK (json_valid(`custom_fields`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($sql);

        $sql = <<<'SQL'
            CREATE TABLE `number_range_channel` (
              `id` binary(16) NOT NULL,
              `number_range_id` binary(16) NOT NULL,
              `channel_id` binary(16) DEFAULT NULL,
              `number_range_type_id` binary(16) NOT NULL,
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uniq.numer_range_id__channel_id` (`number_range_id`,`channel_id`),
              KEY `fk.number_range_channel.channel_id` (`channel_id`),
              KEY `fk.number_range_channel.number_range_type_id` (`number_range_type_id`),
              CONSTRAINT `fk.number_range_channel.number_range_id` FOREIGN KEY (`number_range_id`) REFERENCES `number_range` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk.number_range_channel.number_range_type_id` FOREIGN KEY (`number_range_type_id`) REFERENCES `number_range_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk.number_range_channel.channel_id` FOREIGN KEY (`channel_id`) REFERENCES `channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
