<?php declare(strict_types=1);

namespace Cicada\Core\Installer\Database;

use Doctrine\DBAL\Connection;
use Psr\Log\NullLogger;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationCollectionLoader;
use Cicada\Core\Framework\Migration\MigrationRuntime;
use Cicada\Core\Framework\Migration\MigrationSource;

/**
 * @internal
 */
#[Package('core')]
class MigrationCollectionFactory
{
    public function __construct(private readonly string $projectDir)
    {
    }

    public function getMigrationCollectionLoader(Connection $connection): MigrationCollectionLoader
    {
        return new MigrationCollectionLoader(
            $connection,
            new MigrationRuntime($connection, new NullLogger()),
            $this->collect()
        );
    }

    /**
     * @return list<MigrationSource>
     */
    private function collect(): array
    {
        return [
            new MigrationSource('core', []),
            $this->createMigrationSource('V6_3'),
            $this->createMigrationSource('V6_4'),
            $this->createMigrationSource('V6_5'),
            $this->createMigrationSource('V6_6'),
        ];
    }

    private function createMigrationSource(string $version): MigrationSource
    {
        if (file_exists($this->projectDir . '/platform/src/Core/schema.sql')) {
            $coreBasePath = $this->projectDir . '/platform/src/Core';
            $frontendBasePath = $this->projectDir . '/platform/src/Frontend';
            $adminBasePath = $this->projectDir . '/platform/src/Administration';
        } elseif (file_exists($this->projectDir . '/src/Core/schema.sql')) {
            $coreBasePath = $this->projectDir . '/src/Core';
            $frontendBasePath = $this->projectDir . '/src/Frontend';
            $adminBasePath = $this->projectDir . '/src/Administration';
        } elseif (file_exists($this->projectDir . '/vendor/cicada/platform/src/Core/schema.sql')) {
            $coreBasePath = $this->projectDir . '/vendor/cicada/platform/src/Core';
            $frontendBasePath = $this->projectDir . '/vendor/cicada/platform/src/Frontend';
            $adminBasePath = $this->projectDir . '/vendor/cicada/platform/src/Administration';
        } else {
            $coreBasePath = $this->projectDir . '/vendor/cicada/core';
            $frontendBasePath = $this->projectDir . '/vendor/cicada/frontend';
            $adminBasePath = $this->projectDir . '/vendor/cicada/administration';
        }

        $hasFrontendMigrations = is_dir($frontendBasePath);
        $hasAdminMigrations = is_dir($adminBasePath);

        $source = new MigrationSource('core.' . $version, [
            \sprintf('%s/Migration/%s', $coreBasePath, $version) => \sprintf('Cicada\\Core\\Migration\\%s', $version),
        ]);

        if ($hasFrontendMigrations) {
            $source->addDirectory(\sprintf('%s/Migration/%s', $frontendBasePath, $version), \sprintf('Cicada\\Frontend\\Migration\\%s', $version));
        }

        if ($hasAdminMigrations) {
            $source->addDirectory(\sprintf('%s/Migration/%s', $adminBasePath, $version), \sprintf('Cicada\\Administration\\Migration\\%s', $version));
        }

        return $source;
    }
}
