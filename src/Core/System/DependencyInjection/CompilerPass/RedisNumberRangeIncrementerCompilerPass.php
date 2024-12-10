<?php declare(strict_types=1);

namespace Cicada\Core\System\DependencyInjection\CompilerPass;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\DependencyInjection\DependencyInjectionException;
use Cicada\Core\System\NumberRange\ValueGenerator\Pattern\IncrementStorage\IncrementRedisStorage;
use Cicada\Core\System\NumberRange\ValueGenerator\Pattern\IncrementStorage\IncrementSqlStorage;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
#[Package('core')]
class RedisNumberRangeIncrementerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $storage = $container->getParameter('cicada.number_range.increment_storage');

        switch ($storage) {
            case 'mysql':
                $container->removeDefinition('cicada.number_range.redis');
                $container->removeDefinition(IncrementRedisStorage::class);
                break;
            case 'redis':
                if (
                    !$container->hasParameter('cicada.number_range.config.dsn') // @deprecated tag:v6.7.0 - remove this line (as config.dsn will be removed)
                    && $container->getParameter('cicada.number_range.config.connection') === null
                ) {
                    throw DependencyInjectionException::redisNotConfiguredForNumberRangeIncrementer();
                }

                $container->removeDefinition(IncrementSqlStorage::class);
                break;
        }
    }
}
