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
class Migration1536232600DB extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536232600;
    }

    public function update(Connection $connection): void
    {
        $sql = file_get_contents(__DIR__ . '/fixtures/db.sql');
        if ($sql !== false) {
            $connection->executeStatement($sql);
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
