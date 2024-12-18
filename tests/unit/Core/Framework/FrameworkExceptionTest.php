<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\FrameworkException;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
#[CoversClass(FrameworkException::class)]
class FrameworkExceptionTest extends TestCase
{
    public function testProjectDirNotExists(): void
    {
        static::expectException(FrameworkException::class);
        static::expectExceptionMessage('Project directory "test" does not exist.');

        throw FrameworkException::projectDirNotExists('test');
    }

    public function testCollectionElementInvalidType(): void
    {
        static::expectException(FrameworkException::class);

        static::expectExceptionMessage('Expected collection element of type foo got bar');

        throw FrameworkException::collectionElementInvalidType('foo', 'bar');
    }
}
