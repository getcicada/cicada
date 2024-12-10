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
class Migration1536233310ScheduledTask extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536233310;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE `scheduled_task` (
              `id` binary(16) NOT NULL,
              `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
              `scheduled_task_class` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
              `run_interval` int NOT NULL,
              `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
              `last_execution_time` datetime(3) DEFAULT NULL,
              `next_execution_time` datetime(3) NOT NULL,
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              `default_run_interval` int NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uniq.scheduled_task.scheduled_task_class` (`scheduled_task_class`),
              CONSTRAINT `check.scheduled_task.run_interval` CHECK ((`run_interval` >= 1))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
