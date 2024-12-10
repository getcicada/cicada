<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use Cicada\Core\Framework\DataAbstractionLayer\Dbal\ExceptionHandlerInterface;
use Cicada\Core\Framework\DataAbstractionLayer\TechnicalNameExceptionHandler;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
#[CoversClass(TechnicalNameExceptionHandler::class)]
class TechnicalNameExceptionHandlerTest extends TestCase
{
    public function testPriority(): void
    {
        static::assertSame(ExceptionHandlerInterface::PRIORITY_DEFAULT, (new TechnicalNameExceptionHandler())->getPriority());
    }

    public function testUnrelatedException(): void
    {
        $dbalE = new \Exception(
            'An exception occurred while executing a query: SQLSTATE[23000]: '
            . 'Integrity constraint violation: 1451 Cannot delete or update a parent row: '
            . 'a foreign key constraint fails '
            . '(`shopware`.`theme_media`, CONSTRAINT `fk.theme_media.media_id` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE)'
        );

        $handler = new TechnicalNameExceptionHandler();
        $e = $handler->matchException($dbalE);

        static::assertNull($e);
    }
}
