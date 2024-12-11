<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Frontend\Theme\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\TestDefaults;
use Cicada\Frontend\Theme\Message\CompileThemeMessage;

/**
 * @internal
 */
#[CoversClass(CompileThemeMessage::class)]
class CompileThemeMessageTest extends TestCase
{
    public function testStruct(): void
    {
        $themeId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $message = new CompileThemeMessage(TestDefaults::CHANNEL, $themeId, true, $context);

        static::assertEquals($themeId, $message->getThemeId());
        static::assertEquals(TestDefaults::CHANNEL, $message->getChannelId());
        static::assertTrue($message->isWithAssets());
        static::assertEquals($context, $message->getContext());
    }
}
