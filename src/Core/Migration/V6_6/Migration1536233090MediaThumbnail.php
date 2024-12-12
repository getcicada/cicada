<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Doctrine\DBAL\Connection;
use CIcada\Core\Framework\Log\Package;
use CIcada\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1536233090MediaThumbnail extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536233090;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE `media_thumbnail`
            (
                `id`            binary(16) NOT NULL,
                `media_id`      binary(16) NOT NULL,
                `width`         int unsigned NOT NULL,
                `height`        int unsigned NOT NULL,
                `custom_fields` json                                     DEFAULT NULL,
                `created_at`    datetime(3) NOT NULL,
                `updated_at`    datetime(3) DEFAULT NULL,
                `path`          varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY             `fk.media_thumbnail.media_id` (`media_id`),
                CONSTRAINT `fk.media_thumbnail.media_id` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `json.media_thumbnail.custom_fields` CHECK (json_valid(`custom_fields`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
