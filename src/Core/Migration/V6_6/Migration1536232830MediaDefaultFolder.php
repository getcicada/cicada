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
class Migration1536232830MediaDefaultFolder extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536232830;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE `media_default_folder`
            (
                `id`            binary(16) NOT NULL,
                `entity`        varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                `custom_fields` json DEFAULT NULL,
                `created_at`    datetime(3) NOT NULL,
                `updated_at`    datetime(3) DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq.media_default_folder.entity` (`entity`),
                CONSTRAINT `json.media_default_folder.custom_fields` CHECK (json_valid(`custom_fields`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // no destructive changes
    }
}
