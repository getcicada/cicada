<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

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
class Migration1558505525Logging extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1558505525;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE `log_entry` (
              `id` binary(16) NOT NULL,
              `message` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
              `level` smallint NOT NULL,
              `channel` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
              `context` json DEFAULT NULL,
              `extra` json DEFAULT NULL,
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `idx.log_entry.created_at` (`created_at`),
              CONSTRAINT `json.log_entry.context` CHECK (json_valid(`context`)),
              CONSTRAINT `json.log_entry.extra` CHECK (json_valid(`extra`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $connection->insert('system_config', [
            'id' => Uuid::randomBytes(),
            'configuration_key' => 'core.logging.cleanupInterval',
            'configuration_value' => '{"_value": "86400"}',
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        $connection->insert('system_config', [
            'id' => Uuid::randomBytes(),
            'configuration_key' => 'core.logging.entryLimit',
            'configuration_value' => '{"_value": "10000000"}',
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        $connection->insert('system_config', [
            'id' => Uuid::randomBytes(),
            'configuration_key' => 'core.logging.entryLifetimeSeconds',
            'configuration_value' => '{"_value": "2678400"}', // one month
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}