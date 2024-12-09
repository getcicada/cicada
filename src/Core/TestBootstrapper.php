<?php declare(strict_types=1);

namespace Cicada\Core;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Composer\Autoload\ClassLoader;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpKernel\KernelInterface;

#[Package('core')]
class TestBootstrapper
{
    private ?ClassLoader $classLoader = null;

    private ?string $projectDir = null;
    private bool $loadEnvFile = true;
    private ?string $databaseUrl = null;

    private bool $platformEmbedded = true;
    private ?bool $forceInstall = null;
    private ?OutputInterface $output = null;

    public function bootstrap(): TestBootstrapper
    {
        $_SERVER['PROJECT_ROOT'] = $_ENV['PROJECT_ROOT'] = $this->getProjectDir();
        if (!\defined('TEST_PROJECT_DIR')) {
            \define('TEST_PROJECT_DIR', $_SERVER['PROJECT_ROOT']);
        }
        $classLoader = $this->getClassLoader();

        if ($this->loadEnvFile) {
            $this->loadEnvFile();
        }
        $_SERVER['DATABASE_URL'] = $_ENV['DATABASE_URL'] = $this->getDatabaseUrl();
        KernelLifecycleManager::prepare($classLoader);
        if ($this->isForceInstall() || !$this->dbExists()) {
            $this->install();
        }

        return $this;
    }
    private function install(): void
    {
        $application = new Application($this->getKernel());

        $returnCode = $application->doRun(
            new ArrayInput(
                [
                    'command' => 'system:install',
                    '--create-database' => true,
                    '--force' => true,
                    '--drop-database' => true
                ]
            ),
            $this->getOutput()
        );
        if ($returnCode !== Command::SUCCESS) {
            throw new \RuntimeException('system:install failed');
        }

        // create new kernel after install
        KernelLifecycleManager::bootKernel(false);
    }
    public function getOutput(): OutputInterface
    {
        if ($this->output !== null) {
            return $this->output;
        }

        return $this->output = new ConsoleOutput();
    }

    private function getKernel(): KernelInterface
    {
        return KernelLifecycleManager::getKernel();
    }

    private function dbExists(): bool
    {
        try {
            $connection = $this->getContainer()->get(Connection::class);
            $connection->executeQuery('SELECT 1 FROM `plugin`')->fetchAllAssociative();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
    public function isForceInstall(): bool
    {
        if ($this->forceInstall !== null) {
            return $this->forceInstall;
        }

        return $this->forceInstall = (bool) ($_SERVER['FORCE_INSTALL'] ?? false);
    }
    public function setPlatformEmbedded(bool $platformEmbedded): TestBootstrapper
    {
        $this->platformEmbedded = $platformEmbedded;

        return $this;
    }
    public function getDatabaseUrl(): string
    {
        if ($this->databaseUrl !== null) {
            return $this->databaseUrl;
        }

        $dbUrlParts = parse_url($_SERVER['DATABASE_URL'] ?? '') ?: [];

        $testToken = getenv('TEST_TOKEN');
        $dbUrlParts['path'] ??= 'root';

        // allows using the same database during development, by setting TEST_TOKEN=none
        if ($testToken !== 'none' && !str_ends_with($dbUrlParts['path'], 'test')) {
            $dbUrlParts['path'] .= '_' . ($testToken ?: 'test');
        }

        $auth = isset($dbUrlParts['user']) ? ($dbUrlParts['user'] . (isset($dbUrlParts['pass']) ? (':' . $dbUrlParts['pass']) : '') . '@') : '';

        return $this->databaseUrl = \sprintf(
            '%s://%s%s%s%s%s',
            $dbUrlParts['scheme'] ?? 'mysql',
            $auth,
            $dbUrlParts['host'] ?? 'localhost',
            isset($dbUrlParts['port']) ? (':' . $dbUrlParts['port']) : '',
            $dbUrlParts['path'],
            isset($dbUrlParts['query']) ? ('?' . $dbUrlParts['query']) : ''
        );
    }
    private function loadEnvFile(): void
    {
        if (!class_exists(Dotenv::class)) {
            throw new \RuntimeException('APP_ENV environment variable is not defined. You need to define environment variables for configuration or add "symfony/dotenv" as a Composer dependency to load variables from a .env file.');
        }

        $envFilePath = $this->getProjectDir() . '/.env';
        if (\is_file($envFilePath) || \is_file($envFilePath . '.dist') || \is_file($envFilePath . '.local.php')) {
            (new Dotenv())->usePutenv()->bootEnv($envFilePath);
        }
    }
    public function getClassLoader(): ClassLoader
    {
        if ($this->classLoader !== null) {
            return $this->classLoader;
        }

        $classLoader = require $this->getProjectDir() . '/vendor/autoload.php';


        $this->classLoader = $classLoader;

        return $classLoader;
    }

    public function getProjectDir(): string
    {
        if ($this->projectDir !== null) {
            return $this->projectDir;
        }

        if (isset($_SERVER['PROJECT_ROOT']) && \is_dir($_SERVER['PROJECT_ROOT'])) {
            return $this->projectDir = $_SERVER['PROJECT_ROOT'];
        }

        if (isset($_ENV['PROJECT_ROOT']) && \is_dir($_ENV['PROJECT_ROOT'])) {
            return $this->projectDir = $_ENV['PROJECT_ROOT'];
        }

        // only test cwd if it's not platform embedded (custom/plugins)
        if (!$this->platformEmbedded && \is_dir('vendor')) {
            return $this->projectDir = (string)getcwd();
        }

        $dir = $rootDir = __DIR__;
        while (!\is_dir($dir . '/vendor')) {
            if ($dir === \dirname($dir)) {
                return $rootDir;
            }
            $dir = \dirname($dir);
        }

        return $this->projectDir = $dir;
    }
}