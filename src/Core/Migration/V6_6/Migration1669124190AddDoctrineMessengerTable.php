<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Doctrine\DBAL\Connection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('core')]
class Migration1669124190AddDoctrineMessengerTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1669124190;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            '
                CREATE TABLE `messenger_messages` (
                  `id` bigint NOT NULL AUTO_INCREMENT,
                  `body` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
                  `headers` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
                  `queue_name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
                  `created_at` datetime NOT NULL,
                  `available_at` datetime NOT NULL,
                  `delivered_at` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `queue_name` (`queue_name`),
                  KEY `available_at` (`available_at`),
                  KEY `delivered_at` (`delivered_at`)
                ) ENGINE=InnoDB AUTO_INCREMENT=107 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            '
        );
    }

    public function updateDestructive(Connection $connection): void
    {
        $this->dropTableIfExists($connection, 'dead_message');
    }
}
