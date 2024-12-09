<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Increment;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Increment\AbstractIncrementer;
use Cicada\Core\Framework\Increment\ArrayIncrementer;
use Cicada\Core\Framework\Increment\IncrementerGatewayCompilerPass;
use Cicada\Core\Framework\Increment\MySQLIncrementer;
use Cicada\Core\Framework\Increment\RedisIncrementer;
use Cicada\Core\Test\Annotation\DisabledFeatures;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @internal
 */
#[CoversClass(IncrementerGatewayCompilerPass::class)]
class IncrementerGatewayCompilerPassTest extends TestCase
{
    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('cicada.increment', [
            'user_activity' => [
                'type' => 'mysql',
            ],
            'message_queue' => [
                'type' => 'redis',
                'config' => ['connection' => 'redis_incrementer'],
            ],
            'another_pool' => [
                'type' => 'array',
            ],
        ]);

        $container->register('cicada.increment.gateway.array', ArrayIncrementer::class)
            ->addArgument('');

        $container->register('cicada.increment.gateway.mysql', MySQLIncrementer::class)
            ->addArgument('')
            ->addArgument($this->createMock(Connection::class));

        $entityCompilerPass = new IncrementerGatewayCompilerPass();
        $entityCompilerPass->process($container);

        // user_activity pool is registered
        static::assertTrue($container->hasDefinition('cicada.increment.user_activity.gateway.mysql'));
        $definition = $container->getDefinition('cicada.increment.user_activity.gateway.mysql');
        static::assertEquals(MySQLIncrementer::class, $definition->getClass());
        static::assertTrue($definition->hasTag('cicada.increment.gateway'));

        // message_queue pool is registered
        static::assertTrue($container->hasDefinition('cicada.increment.message_queue.redis_adapter'));
        static::assertTrue($container->hasDefinition('cicada.increment.message_queue.gateway.redis'));
        $definition = $container->getDefinition('cicada.increment.message_queue.gateway.redis');
        static::assertEquals(RedisIncrementer::class, $definition->getClass());
        static::assertTrue($definition->hasTag('cicada.increment.gateway'));

        // another_pool is registered
        static::assertNotNull($container->hasDefinition('cicada.increment.message_queue.gateway.redis'));
        $definition = $container->getDefinition('cicada.increment.message_queue.gateway.redis');
        static::assertEquals(RedisIncrementer::class, $definition->getClass());
        static::assertTrue($definition->hasTag('cicada.increment.gateway'));
    }

    public function testCustomPoolGateway(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('cicada.increment', ['custom_pool' => ['type' => 'custom_type']]);

        $customGateway = new class extends AbstractIncrementer {
            public function decrement(string $cluster, string $key): void
            {
            }

            public function increment(string $cluster, string $key): void
            {
            }

            /**
             * @return array<string, array<string, mixed>>
             */
            public function list(string $cluster, int $limit = 5, int $offset = 0): array
            {
                return [];
            }

            public function reset(string $cluster, ?string $key = null): void
            {
            }

            public function getPool(): string
            {
                return 'custom-pool';
            }
        };

        $container->setDefinition('cicada.increment.custom_pool.gateway.custom_type', new Definition($customGateway::class));

        $entityCompilerPass = new IncrementerGatewayCompilerPass();
        $entityCompilerPass->process($container);

        // custom_pool pool is registered
        static::assertTrue($container->hasDefinition('cicada.increment.custom_pool.gateway.custom_type'));
        $definition = $container->getDefinition('cicada.increment.custom_pool.gateway.custom_type');
        static::assertEquals($customGateway::class, $definition->getClass());
        static::assertTrue($definition->hasTag('cicada.increment.gateway'));
    }

    public function testInvalidCustomPoolGateway(): void
    {
        static::expectException(\RuntimeException::class);
        $container = new ContainerBuilder();
        $container->setParameter('cicada.increment', ['custom_pool' => []]);
        $container->setParameter('cicada.increment.custom_pool.type', 'custom_type');

        $customGateway = new class {
            public function getPool(): string
            {
                return 'custom-pool';
            }
        };

        $container->setDefinition('cicada.increment.custom_pool.gateway.custom_type', new Definition($customGateway::class));

        $entityCompilerPass = new IncrementerGatewayCompilerPass();
        $entityCompilerPass->process($container);

        // custom_pool pool is registered
        static::assertTrue($container->hasDefinition('cicada.increment.custom_pool.gateway.custom_type'));
        $definition = $container->getDefinition('cicada.increment.custom_pool.gateway.custom_type');
        static::assertEquals($customGateway::class, $definition->getClass());
        static::assertTrue($definition->hasTag('cicada.increment.gateway'));
    }

    public function testInvalidType(): void
    {
        static::expectException(\RuntimeException::class);
        static::expectExceptionMessage('Can not find increment gateway for configured type foo of pool custom_pool, expected service id cicada.increment.custom_pool.gateway.foo can not be found');
        $container = new ContainerBuilder();
        $container->setParameter('cicada.increment', ['custom_pool' => [
            'type' => 'foo',
        ]]);
        $container->setParameter('cicada.increment.custom_pool.type', 'invalid');

        $entityCompilerPass = new IncrementerGatewayCompilerPass();
        $entityCompilerPass->process($container);
    }

    public function testInvalidAdapterClass(): void
    {
        static::expectException(\RuntimeException::class);
        static::expectExceptionMessage('Increment gateway with id cicada.increment.custom_pool.gateway.array, expected service instance of Cicada\Core\Framework\Increment\AbstractIncrementer');
        $container = new ContainerBuilder();
        $container->setParameter('cicada.increment', ['custom_pool' => ['type' => 'array']]);
        $container->setParameter('cicada.increment.custom_pool.type', 'custom_type');
        $container->setDefinition('cicada.increment.gateway.array', new Definition(\ArrayObject::class));

        $entityCompilerPass = new IncrementerGatewayCompilerPass();
        $entityCompilerPass->process($container);
    }

    public function testInvalidRedisAdapter(): void
    {
        static::expectException(\RuntimeException::class);
        static::expectExceptionMessage('Can not find increment gateway for configured type redis of pool custom_pool, expected service id cicada.increment.custom_pool.gateway.redis can not be found');

        $container = new ContainerBuilder();
        $container->setParameter('cicada.increment', ['custom_pool' => ['type' => 'redis']]);
        $container->setParameter('cicada.increment.custom_pool.type', 'custom_type');

        $entityCompilerPass = new IncrementerGatewayCompilerPass();
        $entityCompilerPass->process($container);
    }

    /**
     * @deprecated tag:v6.7.0 - Remove in 6.7
     */
    #[DisabledFeatures(['v6.7.0.0'])]
    public function testRedisGatewayWithUrl(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('cicada.increment', [
            'my_pool' => [
                'type' => 'redis',
                'config' => ['url' => 'redis://test'],
            ],
        ]);

        $entityCompilerPass = new IncrementerGatewayCompilerPass();
        $entityCompilerPass->process($container);

        // my_pool is registered
        static::assertTrue($container->hasDefinition('cicada.increment.my_pool.redis_adapter'));
        static::assertTrue($container->hasDefinition('cicada.increment.my_pool.gateway.redis'));
        $definition = $container->getDefinition('cicada.increment.my_pool.gateway.redis');
        static::assertEquals(RedisIncrementer::class, $definition->getClass());
        static::assertTrue($definition->hasTag('cicada.increment.gateway'));
    }
}
