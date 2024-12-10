<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Event\EventData;

use Cicada\Core\System\User\UserDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Event\EventData\EntityType;
use Cicada\Core\Framework\Event\EventData\EventDataCollection;
use Cicada\Core\Framework\Event\EventData\ScalarValueType;

/**
 * @internal
 */
#[CoversClass(EventDataCollection::class)]
class EventDataCollectionTest extends TestCase
{
    public function testToArray(): void
    {
        $collection = (new EventDataCollection())
            ->add('customer', new EntityType(UserDefinition::class))
            ->add('myBool', new ScalarValueType(ScalarValueType::TYPE_BOOL))
        ;

        $expected = [
            'customer' => [
                'type' => 'entity',
                'entityClass' => UserDefinition::class,
                'entityName' => 'user',
            ],
            'myBool' => [
                'type' => 'bool',
            ],
        ];

        static::assertEquals($expected, $collection->toArray());
    }
}
