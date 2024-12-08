<?php declare(strict_types=1);

namespace Cicada\Core;
use Cicada\Core\Framework\Adapter\Database\MySQLFactory;
use Cicada\Core\Framework\Log\Package;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as HttpKernel;

#[Package('core')]
class Kernel extends HttpKernel
{
    use MicroKernelTrait;

    final public const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    /**
     * @var string Fallback version if nothing is provided via kernel constructor
     */
    final public const CICADA_FALLBACK_VERSION = '6.6.9999999-dev';

    protected static ?Connection $connection = null;

    /**
     * @internal
     *
     */
    public function __construct(
        string $environment,
        bool $debug,
        string $version,
        Connection $connection,
        protected string $projectDir
    ){
        date_default_timezone_set('Asia/Shanghai');
        parent::__construct($environment, $debug);
        self::$connection = $connection;
    }

    public static function getConnection(): Connection
    {
        if (self::$connection) {
            return self::$connection;
        }

        self::$connection = MySQLFactory::create();

        return self::$connection;
    }

    public function getProjectDir(): string
    {
        return $this->projectDir;
    }

}