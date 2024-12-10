<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Adapter\Redis;

use Psr\Container\ContainerInterface;
use Cicada\Core\Framework\Adapter\AdapterException;
use Cicada\Core\Framework\Adapter\Cache\RedisConnectionFactory;
use Cicada\Core\Framework\Log\Package;

/**
 * RedisConnection corresponds to a return type of symfony's RedisAdapter::createConnection and may change with symfony update.
 *
 * @phpstan-type RedisConnection \Redis|\RedisArray|\RedisCluster|\Predis\ClientInterface|\Relay\Relay
 */
#[Package('core')]
class RedisConnectionProvider
{
    /**
     * @internal
     */
    public function __construct(
        private ContainerInterface $serviceLocator,

        /**
         * @deprecated tag:v6.7.0 - Remove in 6.7
         */
        private RedisConnectionFactory $redisConnectionFactory,
    ) {
    }

    /**
     * @return RedisConnection
     */
    public function getConnection(string $connectionName)
    {
        if (!$this->hasConnection($connectionName)) {
            throw AdapterException::unknownRedisConnection($connectionName);
        }

        return $this->serviceLocator->get($this->getServiceName($connectionName));
    }

    public function hasConnection(string $connectionName): bool
    {
        return $this->serviceLocator->has($this->getServiceName($connectionName));
    }

    private function getServiceName(string $connectionName): string
    {
        return 'cicada.redis.connection.' . $connectionName;
    }
}
