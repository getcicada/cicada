<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Frontend\Theme\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Administration\Notification\NotificationService;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Channel\ChannelEntity;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Cicada\Core\Test\TestDefaults;
use Cicada\Frontend\Theme\ConfigLoader\AbstractConfigLoader;
use Cicada\Frontend\Theme\Message\CompileThemeHandler;
use Cicada\Frontend\Theme\Message\CompileThemeMessage;
use Cicada\Frontend\Theme\FrontendPluginRegistryInterface;
use Cicada\Frontend\Theme\ThemeCompiler;

/**
 * @internal
 */
#[CoversClass(CompileThemeHandler::class)]
class CompileThemeHandlerTest extends TestCase
{
    public function testHandleMessageCompile(): void
    {
        $themeCompilerMock = $this->createMock(ThemeCompiler::class);
        $notificationServiceMock = $this->createMock(NotificationService::class);
        $themeId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $message = new CompileThemeMessage(TestDefaults::CHANNEL, $themeId, true, $context);

        $themeCompilerMock->expects(static::once())->method('compileTheme');

        $scEntity = new ChannelEntity();
        $scEntity->setUniqueIdentifier(Uuid::randomHex());
        $scEntity->setName('Test Channel');

        /** @var StaticEntityRepository<EntityCollection<ChannelEntity>> $channelRep */
        $channelRep = new StaticEntityRepository([new EntityCollection([$scEntity])]);

        $handler = new CompileThemeHandler(
            $themeCompilerMock,
            $this->createMock(AbstractConfigLoader::class),
            $this->createMock(FrontendPluginRegistryInterface::class),
            $notificationServiceMock,
            $channelRep
        );

        $handler($message);
    }
}
