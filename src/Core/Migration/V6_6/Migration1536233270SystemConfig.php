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
class Migration1536233270SystemConfig extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536233270;
    }

    public function update(Connection $connection): void
    {
        $query = <<<'SQL'
            CREATE TABLE `system_config` (
              `id` binary(16) NOT NULL,
              `configuration_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
              `configuration_value` json NOT NULL,
              `channel_id` binary(16) DEFAULT NULL,
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uniq.system_config.configuration_key__channel_id` (`configuration_key`,`channel_id`),
              KEY `fk.system_config.channel_id` (`channel_id`),
              CONSTRAINT `fk.system_config.channel_id` FOREIGN KEY (`channel_id`) REFERENCES `channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `json.system_config.configuration_value` CHECK (json_valid(`configuration_value`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($query);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
