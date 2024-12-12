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
class Migration1536233060MediaFolderConfiguration extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536233060;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE `media_folder_configuration`
            (
                `id`                       binary(16) NOT NULL,
                `create_thumbnails`        tinyint(1) DEFAULT "1",
                `thumbnail_quality`        int  DEFAULT "80",
                `media_thumbnail_sizes_ro` longblob,
                `keep_aspect_ratio`        tinyint(1) DEFAULT "1",
                `private`                  tinyint(1) DEFAULT "0",
                `no_association`           tinyint(1) DEFAULT NULL,
                `custom_fields`            json DEFAULT NULL,
                `created_at`               datetime(3) NOT NULL,
                `updated_at`               datetime(3) DEFAULT NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `json.media_folder_configuration.custom_fields` CHECK (json_valid(`custom_fields`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // no destructive changes
    }
}
