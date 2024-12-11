<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Frontend\Theme\Subscriber;

use Doctrine\DBAL\Exception as DBALException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Context;
use Cicada\Core\System\SystemConfig\Service\ConfigurationService;
use Cicada\Core\Test\TestDefaults;
use Cicada\Frontend\Theme\Event\ThemeCompilerEnrichScssVariablesEvent;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\FrontendPluginConfiguration;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\FrontendPluginConfigurationCollection;
use Cicada\Frontend\Theme\FrontendPluginRegistry;
use Cicada\Frontend\Theme\Subscriber\ThemeCompilerEnrichScssVarSubscriber;

/**
 * @internal
 */
#[CoversClass(ThemeCompilerEnrichScssVarSubscriber::class)]
class ThemeCompilerEnrichScssVarSubscriberTest extends TestCase
{
    /**
     * @var ConfigurationService&MockObject
     */
    private ConfigurationService $configService;

    /**
     * @var FrontendPluginRegistry&MockObject
     */
    private FrontendPluginRegistry $storefrontPluginRegistry;

    protected function setUp(): void
    {
        $this->configService = $this->createMock(ConfigurationService::class);
        $this->storefrontPluginRegistry = $this->createMock(FrontendPluginRegistry::class);
    }

    public function testEnrichExtensionVarsReturnsNothingWithNoFrontendPlugin(): void
    {
        $this->configService->expects(static::never())->method('getResolvedConfiguration');

        $subscriber = new ThemeCompilerEnrichScssVarSubscriber($this->configService, $this->storefrontPluginRegistry);

        $subscriber->enrichExtensionVars(
            new ThemeCompilerEnrichScssVariablesEvent(
                [],
                TestDefaults::CHANNEL,
                Context::createDefaultContext()
            )
        );
    }

    public function testDBException(): void
    {
        $this->configService->method('getResolvedConfiguration')->willThrowException(new DBALException('test'));
        $this->storefrontPluginRegistry->method('getConfigurations')->willReturn(
            new FrontendPluginConfigurationCollection([
                new FrontendPluginConfiguration('test'),
            ])
        );
        $subscriber = new ThemeCompilerEnrichScssVarSubscriber($this->configService, $this->storefrontPluginRegistry);

        $exception = null;
        try {
            $subscriber->enrichExtensionVars(
                new ThemeCompilerEnrichScssVariablesEvent(
                    [],
                    TestDefaults::CHANNEL,
                    Context::createDefaultContext()
                )
            );
        } catch (DBALException $exception) {
        }

        static::assertNull($exception);
    }

    /**
     * EnrichScssVarSubscriber doesn't throw an exception if we have corrupted element values.
     * This can happen on updates from older version when the values in the administration where not checked before save
     */
    public function testOutputsPluginCssCorrupt(): void
    {
        $this->configService->method('getResolvedConfiguration')->willReturn([
            'card' => [
                'elements' => [
                    new \DateTime(),
                ],
            ],
        ]);

        $this->storefrontPluginRegistry->method('getConfigurations')->willReturn(
            new FrontendPluginConfigurationCollection([
                new FrontendPluginConfiguration('test'),
            ])
        );
        $subscriber = new ThemeCompilerEnrichScssVarSubscriber($this->configService, $this->storefrontPluginRegistry);

        $event = new ThemeCompilerEnrichScssVariablesEvent(
            ['bla' => 'any'],
            TestDefaults::CHANNEL,
            Context::createDefaultContext()
        );

        $backupEvent = clone $event;

        $subscriber->enrichExtensionVars(
            $event
        );

        static::assertEquals($backupEvent, $event);
    }

    public function testgetSubscribedEventsReturnsOnlyOneTypeOfEvent(): void
    {
        static::assertEquals(
            [
                ThemeCompilerEnrichScssVariablesEvent::class => 'enrichExtensionVars',
            ],
            ThemeCompilerEnrichScssVarSubscriber::getSubscribedEvents()
        );
    }
}
