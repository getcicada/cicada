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
class Migration1583844433AddRefreshTokenTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1583844433;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `refresh_token` (
              `id` binary(16) NOT NULL,
              `user_id` binary(16) NOT NULL,
              `token_id` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
              `issued_at` datetime(3) NOT NULL,
              `expires_at` datetime(3) NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uniq.token_id` (`token_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
