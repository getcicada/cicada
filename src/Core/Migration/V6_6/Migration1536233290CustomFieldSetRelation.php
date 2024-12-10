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
class Migration1536233290CustomFieldSetRelation extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536233290;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE `custom_field_set_relation` (
              `id` binary(16) NOT NULL,
              `set_id` binary(16) NOT NULL,
              `entity_name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uniq.custom_field_set_relation.entity_name` (`set_id`,`entity_name`),
              CONSTRAINT `fk.custom_field_set_relation.set_id` FOREIGN KEY (`set_id`) REFERENCES `custom_field_set` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
