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
class Migration1536233070MediaThumbnailSize extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536233070;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE `media_thumbnail_size`
            (
                `id`            binary(16) NOT NULL,
                `width`         int NOT NULL,
                `height`        int NOT NULL,
                `custom_fields` json DEFAULT NULL,
                `created_at`    datetime(3) NOT NULL,
                `updated_at`    datetime(3) DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq.width` (`width`,`height`),
                CONSTRAINT `json.media_thumbnail_size.custom_fields` CHECK (json_valid(`custom_fields`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // no destructive changes
    }
}
