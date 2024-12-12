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
class Migration1578491480Hreflang extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1578491480;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `channel` ADD `hreflang_default_domain_id` BINARY(16) NULL AFTER `navigation_category_depth`;');

        $connection->executeStatement('
            ALTER TABLE `channel`
            ADD CONSTRAINT `fk.channel.hreflang_default_domain_id`
            FOREIGN KEY (`hreflang_default_domain_id`)
            REFERENCES `channel_domain` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
        ');

        $connection->executeStatement('ALTER TABLE `channel` ADD `hreflang_active` tinyint(1) unsigned DEFAULT 0 AFTER `navigation_category_depth`;');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
