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
class Migration1536232810User extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536232810;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE `user` (
              `id` binary(16) NOT NULL,
              `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
              `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
              `name` varchar(255) COLLATE utf8mb4_unicode_ci  NULL,
              `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
              `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
              `active` tinyint(1) NOT NULL DEFAULT "0",
              `avatar_id` binary(16) DEFAULT NULL,
              `admin` tinyint(1) DEFAULT NULL,
              `locale_id` binary(16) NOT NULL,
              `store_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `last_updated_password_at` datetime(3) DEFAULT NULL,
              `time_zone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT "UTC",
              `custom_fields` json DEFAULT NULL,
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `fk.user.avatar_id` (`avatar_id`),
              UNIQUE KEY `uniq.user.username` (`username`),
              KEY `fk.user.locale_id` (`locale_id`),
              CONSTRAINT `fk.user.locale_id` FOREIGN KEY (`locale_id`) REFERENCES `locale` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
              CONSTRAINT `json.user.custom_fields` CHECK (json_valid(`custom_fields`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
