<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Event\EventData;

use Cicada\Core\Framework\Event\EventData\EntityType;
use Cicada\Core\System\User\UserDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(EntityType::class)]
class EntityTypeTest extends TestCase
{
    public function testToArray(): void
    {
        $definition = UserDefinition::class;

        $expected = [
            'type' => 'entity',
            'entityClass' => UserDefinition::class,
            'entityName' => 'user',
        ];

        static::assertEquals($expected, (new EntityType($definition))->toArray());
        static::assertEquals($expected, (new EntityType(new UserDefinition()))->toArray());
    }
}
