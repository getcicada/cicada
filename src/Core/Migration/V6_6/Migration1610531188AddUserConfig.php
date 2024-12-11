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
class Migration1610531188AddUserConfig extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1610531188;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `user_config` (
              `id` binary(16) NOT NULL,
              `user_id` binary(16) NOT NULL,
              `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
              `value` json DEFAULT NULL,
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uniq.user_id_key` (`user_id`,`key`),
              CONSTRAINT `fk.user_config.user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `json.user_config.value` CHECK (json_valid(`value`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
