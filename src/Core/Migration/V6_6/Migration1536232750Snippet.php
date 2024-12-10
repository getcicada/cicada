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
class Migration1536232750Snippet extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536232750;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE `snippet` (
              `id` binary(16) NOT NULL,
              `translation_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
              `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
              `author` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
              `snippet_set_id` binary(16) NOT NULL,
              `custom_fields` json DEFAULT NULL,
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uniq.snippet_set_id_translation_key` (`snippet_set_id`,`translation_key`),
              CONSTRAINT `fk.snippet.snippet_set_id` FOREIGN KEY (`snippet_set_id`) REFERENCES `snippet_set` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `json.snippet.custom_fields` CHECK (json_valid(`custom_fields`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
