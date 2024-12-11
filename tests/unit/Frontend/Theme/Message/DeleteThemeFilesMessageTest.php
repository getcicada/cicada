<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Frontend\Theme\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Frontend\Theme\Message\DeleteThemeFilesMessage;

/**
 * @internal
 */
#[CoversClass(DeleteThemeFilesMessage::class)]
class DeleteThemeFilesMessageTest extends TestCase
{
    public function testStruct(): void
    {
        $message = new DeleteThemeFilesMessage('path', 'channel', 'theme');

        static::assertEquals('path', $message->getThemePath());
        static::assertEquals('channel', $message->getChannelId());
        static::assertEquals('theme', $message->getThemeId());
    }
}
