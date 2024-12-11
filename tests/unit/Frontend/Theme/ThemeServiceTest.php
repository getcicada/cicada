<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Frontend\Theme;

use Cicada\Administration\Notification\NotificationService;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Channel\ChannelCollection;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\TestDefaults;
use Cicada\Frontend\Theme\ConfigLoader\DatabaseConfigLoader;
use Cicada\Frontend\Theme\ConfigLoader\StaticFileConfigLoader;
use Cicada\Frontend\Theme\Event\ThemeAssignedEvent;
use Cicada\Frontend\Theme\Event\ThemeConfigChangedEvent;
use Cicada\Frontend\Theme\Event\ThemeConfigResetEvent;
use Cicada\Frontend\Theme\Exception\ThemeException;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\FrontendPluginConfiguration;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\FrontendPluginConfigurationCollection;
use Cicada\Frontend\Theme\FrontendPluginRegistry;
use Cicada\Frontend\Theme\Message\CompileThemeMessage;
use Cicada\Frontend\Theme\ThemeCollection;
use Cicada\Frontend\Theme\ThemeCompiler;
use Cicada\Frontend\Theme\ThemeEntity;
use Cicada\Frontend\Theme\ThemeService;
use Cicada\Tests\Unit\Frontend\Theme\fixtures\ThemeFixtures;
use Doctrine\DBAL\Connection;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBus;

/**
 * @internal
 */
#[CoversClass(ThemeService::class)]
class ThemeServiceTest extends TestCase
{
    private Connection&MockObject $connectionMock;

    private FrontendPluginRegistry&MockObject $storefrontPluginRegistryMock;

    private EntityRepository&MockObject $themeRepositoryMock;

    private EntityRepository&MockObject $themeChannelRepositoryMock;

    private ThemeCompiler&MockObject $themeCompilerMock;

    private EventDispatcher&MockObject $eventDispatcherMock;

    private ThemeService $themeService;

    private Context $context;

    private SystemConfigService&MockObject $systemConfigMock;

    private MessageBus&MockObject $messageBusMock;

    protected function setUp(): void
    {
        $this->connectionMock = $this->createMock(Connection::class);
        $this->storefrontPluginRegistryMock = $this->createMock(FrontendPluginRegistry::class);
        $this->themeRepositoryMock = $this->createMock(EntityRepository::class);
        $this->themeChannelRepositoryMock = $this->createMock(EntityRepository::class);
        $this->themeCompilerMock = $this->createMock(ThemeCompiler::class);
        $this->eventDispatcherMock = $this->createMock(EventDispatcher::class);
        $databaseConfigLoaderMock = $this->createMock(DatabaseConfigLoader::class);
        $this->context = Context::createDefaultContext();
        $this->systemConfigMock = $this->createMock(SystemConfigService::class);
        $this->messageBusMock = $this->createMock(MessageBus::class);

        $this->themeService = new ThemeService(
            $this->storefrontPluginRegistryMock,
            $this->themeRepositoryMock,
            $this->themeChannelRepositoryMock,
            $this->themeCompilerMock,
            $this->eventDispatcherMock,
            $databaseConfigLoaderMock,
            $this->connectionMock,
            $this->systemConfigMock,
            $this->messageBusMock,
            $this->createMock(NotificationService::class)
        );
    }

    public function testAssignTheme(): void
    {
        $themeId = Uuid::randomHex();

        $this->themeChannelRepositoryMock->expects(static::once())->method('upsert')->with(
            [[
                'themeId' => $themeId,
                'channelId' => TestDefaults::CHANNEL,
            ]],
            $this->context
        );

        $this->eventDispatcherMock->expects(static::once())->method('dispatch')->with(
            new ThemeAssignedEvent($themeId, TestDefaults::CHANNEL)
        );

        $this->themeCompilerMock->expects(static::once())->method('compileTheme')->with(
            TestDefaults::CHANNEL,
            $themeId,
            static::anything(),
            static::anything(),
            true,
            $this->context
        );

        $assigned = $this->themeService->assignTheme($themeId, TestDefaults::CHANNEL, $this->context);

        static::assertTrue($assigned);
    }

    public function testAssignThemeSkipCompile(): void
    {
        $themeId = Uuid::randomHex();

        $this->themeChannelRepositoryMock->expects(static::once())->method('upsert')->with(
            [[
                'themeId' => $themeId,
                'channelId' => TestDefaults::CHANNEL,
            ]],
            $this->context
        );

        $this->eventDispatcherMock->expects(static::once())->method('dispatch')->with(
            new ThemeAssignedEvent($themeId, TestDefaults::CHANNEL)
        );

        $this->themeCompilerMock->expects(static::never())->method('compileTheme');

        $assigned = $this->themeService->assignTheme($themeId, TestDefaults::CHANNEL, $this->context, true);

        static::assertTrue($assigned);
    }

    public function testCompileTheme(): void
    {
        $themeId = Uuid::randomHex();

        $this->themeCompilerMock->expects(static::once())->method('compileTheme')->with(
            TestDefaults::CHANNEL,
            $themeId,
            static::anything(),
            static::anything(),
            true,
            $this->context
        );

        $this->themeService->compileTheme(TestDefaults::CHANNEL, $themeId, $this->context);
    }

    public function testCompileThemeAsyncSkipHeader(): void
    {
        $themeId = Uuid::randomHex();

        $this->context->addState(ThemeService::STATE_NO_QUEUE);

        $this->messageBusMock->expects(static::never())->method('dispatch');

        $this->themeCompilerMock->expects(static::once())->method('compileTheme')->with(
            TestDefaults::CHANNEL,
            $themeId,
            static::anything(),
            static::anything(),
            true,
            $this->context
        );

        $this->systemConfigMock->method('get')->with(ThemeService::CONFIG_THEME_COMPILE_ASYNC)->willReturn(true);

        $this->themeService->compileTheme(TestDefaults::CHANNEL, $themeId, $this->context);
    }

    public function testCompileThemeAsyncSetting(): void
    {
        $themeId = Uuid::randomHex();

        $this->themeCompilerMock->expects(static::never())->method('compileTheme');

        $context = $this->context;
        $this->messageBusMock->expects(static::once())->method('dispatch')
            ->willReturnCallback(function () use ($themeId, $context): Envelope {
                return new Envelope(
                    new CompileThemeMessage(
                        TestDefaults::CHANNEL,
                        $themeId,
                        true,
                        $context
                    )
                );
            });

        $this->systemConfigMock->method('get')->with(ThemeService::CONFIG_THEME_COMPILE_ASYNC)->willReturn(true);

        $this->themeService->compileTheme(TestDefaults::CHANNEL, $themeId, $this->context);
    }

    public function testCompileThemeGivenConf(): void
    {
        $themeId = Uuid::randomHex();

        $confCollection = new FrontendPluginConfigurationCollection();

        $this->themeCompilerMock->expects(static::once())->method('compileTheme')->with(
            TestDefaults::CHANNEL,
            $themeId,
            static::anything(),
            $confCollection,
            true,
            $this->context
        );

        $this->themeService->compileTheme(TestDefaults::CHANNEL, $themeId, $this->context, $confCollection);
    }

    public function testCompileThemeWithAssets(): void
    {
        $themeId = Uuid::randomHex();

        $this->themeCompilerMock->expects(static::once())->method('compileTheme')->with(
            TestDefaults::CHANNEL,
            $themeId,
            static::anything(),
            static::anything(),
            false,
            $this->context
        );

        $this->themeService->compileTheme(TestDefaults::CHANNEL, $themeId, $this->context, null, false);
    }

    public function testCompileThemeById(): void
    {
        $themeId = Uuid::randomHex();
        $dependendThemeId = Uuid::randomHex();

        $this->connectionMock->method('fetchAllAssociative')->willReturn(
            [
                [
                    'id' => $themeId,
                    'saleschannelId' => TestDefaults::CHANNEL,
                    'dependentId' => $dependendThemeId,
                    'dsaleschannelId' => TestDefaults::CHANNEL,
                ],
            ]
        );

        $parameters = [];

        $this->themeCompilerMock
            ->expects(static::exactly(2))
            ->method('compileTheme')
            ->willReturnCallback(function ($channelId, $themeId) use (&$parameters): void {
                $parameters[] = [$channelId, $themeId];
            });

        $this->themeService->compileThemeById($themeId, $this->context);

        static::assertSame([
            [
                TestDefaults::CHANNEL,
                $themeId,
            ],
            [
                TestDefaults::CHANNEL,
                $dependendThemeId,
            ],
        ], $parameters);
    }

    public function testUpdateThemeNoTheme(): void
    {
        $themeId = Uuid::randomHex();

        $this->themeRepositoryMock->method('search')->willReturn(
            new EntitySearchResult(
                'theme',
                1,
                new ThemeCollection(
                    [
                        (new ThemeEntity())->assign(
                            [
                                '_uniqueIdentifier' => 'no',
                                'channels' => new ChannelCollection(),
                            ]
                        ),
                    ]
                ),
                null,
                new Criteria(),
                $this->context
            )
        );

        $this->expectException(ThemeException::class);
        $this->expectExceptionMessage(\sprintf('Could not find theme with id "%s"', $themeId));

        $this->themeService->updateTheme($themeId, null, null, $this->context);
    }

    public function testUpdateTheme(): void
    {
        $themeId = Uuid::randomHex();
        $dependendThemeId = Uuid::randomHex();

        $this->connectionMock->method('fetchAllAssociative')->willReturn(
            [
                [
                    'id' => $themeId,
                    'saleschannelId' => TestDefaults::CHANNEL,
                    'dependentId' => $dependendThemeId,
                    'dsaleschannelId' => TestDefaults::CHANNEL,
                ],
            ]
        );

        $this->themeRepositoryMock->method('search')->willReturn(
            new EntitySearchResult(
                'theme',
                1,
                new ThemeCollection(
                    [
                        (new ThemeEntity())->assign(
                            [
                                '_uniqueIdentifier' => $themeId,
                                'channels' => new ChannelCollection(),
                            ]
                        ),
                    ]
                ),
                null,
                new Criteria(),
                $this->context
            )
        );

        $this->themeCompilerMock->expects(static::exactly(2))->method('compileTheme');

        $this->themeService->updateTheme($themeId, null, null, $this->context);
    }

    public function testUpdateThemeWithConfig(): void
    {
        $themeId = Uuid::randomHex();
        $parentThemeId = Uuid::randomHex();
        $dependendThemeId = Uuid::randomHex();

        $this->connectionMock->method('fetchAllAssociative')->willReturn(
            [
                [
                    'id' => $themeId,
                    'saleschannelId' => TestDefaults::CHANNEL,
                    'dependentId' => $dependendThemeId,
                    'dsaleschannelId' => TestDefaults::CHANNEL,
                ],
            ]
        );

        $this->themeRepositoryMock->method('search')->willReturn(
            new EntitySearchResult(
                'theme',
                1,
                new ThemeCollection(
                    [
                        (new ThemeEntity())->assign(
                            [
                                '_uniqueIdentifier' => $themeId,
                                'channels' => new ChannelCollection(),
                                'configValues' => [
                                    'test' => ['value' => ['no_test']],
                                ],
                            ]
                        ),
                    ]
                ),
                null,
                new Criteria(),
                $this->context
            )
        );

        $this->eventDispatcherMock->expects(static::once())->method('dispatch')->with(
            new ThemeConfigChangedEvent($themeId, ['test' => ['value' => ['test']]])
        );

        $this->themeCompilerMock->expects(static::exactly(2))->method('compileTheme');

        $this->themeService->updateTheme($themeId, ['test' => ['value' => ['test']]], $parentThemeId, $this->context);
    }

    public function testUpdateThemeNoChannelAssigned(): void
    {
        $themeId = Uuid::randomHex();

        $this->themeRepositoryMock->method('search')->willReturn(
            new EntitySearchResult(
                'theme',
                1,
                new ThemeCollection(
                    [
                        (new ThemeEntity())->assign(
                            [
                                '_uniqueIdentifier' => $themeId,
                            ]
                        ),
                    ]
                ),
                null,
                new Criteria(),
                $this->context
            )
        );

        $this->themeCompilerMock->expects(static::never())->method('compileTheme');

        $this->themeService->updateTheme($themeId, null, null, $this->context);
    }

    public function testResetTheme(): void
    {
        $themeId = Uuid::randomHex();

        $this->themeRepositoryMock->method('search')->willReturn(
            new EntitySearchResult(
                'theme',
                1,
                new ThemeCollection(
                    [
                        (new ThemeEntity())->assign(
                            [
                                '_uniqueIdentifier' => $themeId,
                            ]
                        ),
                    ]
                ),
                null,
                new Criteria(),
                $this->context
            )
        );

        $this->eventDispatcherMock->expects(static::once())->method('dispatch')->with(
            new ThemeConfigResetEvent($themeId)
        );

        $this->themeRepositoryMock->expects(static::once())->method('update')->with(
            [
                [
                    'id' => $themeId,
                    'configValues' => null,
                ],
            ],
            $this->context
        );

        $this->themeService->resetTheme($themeId, $this->context);
    }

    public function testResetThemeNoTheme(): void
    {
        $themeId = Uuid::randomHex();

        $this->themeRepositoryMock->method('search')->willReturn(
            new EntitySearchResult(
                'theme',
                1,
                new ThemeCollection(
                    [
                        (new ThemeEntity())->assign(
                            [
                                '_uniqueIdentifier' => 'no',
                                'channels' => new ChannelCollection(),
                            ]
                        ),
                    ]
                ),
                null,
                new Criteria(),
                $this->context
            )
        );

        $this->expectException(ThemeException::class);
        $this->expectExceptionMessage(\sprintf('Could not find theme with id "%s"', $themeId));
        $this->themeService->resetTheme($themeId, $this->context);
    }

    public function testGetThemeConfigurationNoTheme(): void
    {
        $themeId = Uuid::randomHex();

        $this->themeRepositoryMock->method('search')->willReturn(
            new EntitySearchResult(
                'theme',
                1,
                new ThemeCollection(
                    [
                        (new ThemeEntity())->assign(
                            [
                                '_uniqueIdentifier' => 'no',
                                'channels' => new ChannelCollection(),
                            ]
                        ),
                    ]
                ),
                null,
                new Criteria(),
                $this->context
            )
        );

        $this->expectException(ThemeException::class);
        $this->expectExceptionMessage(\sprintf('Could not find theme with id "%s"', $themeId));

        $this->themeService->getThemeConfiguration($themeId, false, $this->context);
    }

    /**
     * @param array<string, mixed> $ids
     * @param array<string, mixed>|null $expected
     * @param array<string, mixed>|null $expectedNotTranslated
     * @param array<string, mixed>|null $expectedStructured
     * @param array<string, mixed>|null $expectedStructuredNotTranslated
     */
    #[DataProvider('getThemeCollectionForThemeConfiguration')]
    public function testGetThemeConfiguration(
        array $ids,
        ThemeCollection $themeCollection,
        ?array $expected = null,
        ?array $expectedNotTranslated = null,
        ?array $expectedStructured = null,
        ?array $expectedStructuredNotTranslated = null
    ): void {
        $this->themeRepositoryMock->method('search')->willReturn(
            new EntitySearchResult(
                'theme',
                1,
                $themeCollection,
                null,
                new Criteria(),
                $this->context
            )
        );

        $storefrontPlugin = new FrontendPluginConfiguration('Test');
        $storefrontPlugin->setThemeConfig(ThemeFixtures::getThemeJsonConfig());

        $this->storefrontPluginRegistryMock->method('getConfigurations')->willReturn(
            new FrontendPluginConfigurationCollection(
                [
                    $storefrontPlugin,
                ]
            )
        );

        $config = $this->themeService->getThemeConfiguration($ids['themeId'], true, $this->context);

        static::assertArrayHasKey('fields', $config);
        static::assertArrayHasKey('currentFields', $config);
        static::assertArrayHasKey('baseThemeFields', $config);
        static::assertEquals($expected, $config);
    }

    /**
     * @param array<string, mixed> $ids
     * @param array<string, mixed>|null $expected
     * @param array<string, mixed>|null $expectedNotTranslated
     * @param array<string, mixed>|null $expectedStructured
     * @param array<string, mixed>|null $expectedStructuredNotTranslated
     */
    #[DataProvider('getThemeCollectionForThemeConfiguration')]
    public function testGetThemeConfigurationNoTranslation(
        array $ids,
        ThemeCollection $themeCollection,
        ?array $expected = null,
        ?array $expectedNotTranslated = null,
        ?array $expectedStructured = null,
        ?array $expectedStructuredNotTranslated = null
    ): void {
        if ($expectedNotTranslated !== null) {
            $expected = $expectedNotTranslated;
        }

        $this->themeRepositoryMock->method('search')->willReturn(
            new EntitySearchResult(
                'theme',
                1,
                $themeCollection,
                null,
                new Criteria(),
                $this->context
            )
        );

        $storefrontPlugin = new FrontendPluginConfiguration('Test');
        $storefrontPlugin->setThemeConfig(ThemeFixtures::getThemeJsonConfig());

        $this->storefrontPluginRegistryMock->method('getConfigurations')->willReturn(
            new FrontendPluginConfigurationCollection(
                [
                    $storefrontPlugin,
                ]
            )
        );

        $config = $this->themeService->getThemeConfiguration($ids['themeId'], false, $this->context);

        static::assertArrayHasKey('fields', $config);
        static::assertArrayHasKey('currentFields', $config);
        static::assertArrayHasKey('baseThemeFields', $config);
        static::assertEquals($expected, $config);
    }

    /**
     * @param array<string, mixed> $ids
     * @param array<string, mixed>|null $expected
     * @param array<string, mixed>|null $expectedNotTranslated
     * @param array<string, mixed>|null $expectedStructured
     * @param array<string, mixed>|null $expectedStructuredNotTranslated
     */
    #[DataProvider('getThemeCollectionForThemeConfiguration')]
    public function testGetThemeConfigurationStructured(
        array $ids,
        ThemeCollection $themeCollection,
        ?array $expected = null,
        ?array $expectedNotTranslated = null,
        ?array $expectedStructured = null,
        ?array $expectedStructuredNotTranslated = null
    ): void {
        $this->themeRepositoryMock->method('search')->willReturn(
            new EntitySearchResult(
                'theme',
                1,
                $themeCollection,
                null,
                new Criteria(),
                $this->context
            )
        );

        $storefrontPlugin = new FrontendPluginConfiguration('Test');
        $storefrontPlugin->setThemeConfig(ThemeFixtures::getThemeJsonConfig());

        $this->storefrontPluginRegistryMock->method('getConfigurations')->willReturn(
            new FrontendPluginConfigurationCollection(
                [
                    $storefrontPlugin,
                ]
            )
        );

        $config = $this->themeService->getThemeConfigurationStructuredFields($ids['themeId'], true, $this->context);

        static::assertArrayHasKey('tabs', $config);
        static::assertArrayHasKey('default', $config['tabs']);
        static::assertArrayHasKey('blocks', $config['tabs']['default']);
        static::assertEquals($expectedStructured, $config);
    }

    /**
     * @param array<string, mixed> $ids
     * @param array<string, mixed>|null $expected
     * @param array<string, mixed>|null $expectedNotTranslated
     * @param array<string, mixed>|null $expectedStructured
     * @param array<string, mixed>|null $expectedStructuredNotTranslated
     */
    #[DataProvider('getThemeCollectionForThemeConfiguration')]
    public function testGetThemeConfigurationStructuredNoTranslation(
        array $ids,
        ThemeCollection $themeCollection,
        ?array $expected = null,
        ?array $expectedNotTranslated = null,
        ?array $expectedStructured = null,
        ?array $expectedStructuredNotTranslated = null
    ): void {
        if ($expectedStructuredNotTranslated !== null) {
            $expectedStructured = $expectedStructuredNotTranslated;
        }

        $this->themeRepositoryMock->method('search')->willReturn(
            new EntitySearchResult(
                'theme',
                1,
                $themeCollection,
                null,
                new Criteria(),
                $this->context
            )
        );

        $storefrontPlugin = new FrontendPluginConfiguration('Test');
        $storefrontPlugin->setThemeConfig(ThemeFixtures::getThemeJsonConfig());

        $this->storefrontPluginRegistryMock->method('getConfigurations')->willReturn(
            new FrontendPluginConfigurationCollection(
                [
                    $storefrontPlugin,
                ]
            )
        );

        $config = $this->themeService->getThemeConfigurationStructuredFields($ids['themeId'], false, $this->context);

        static::assertArrayHasKey('tabs', $config);
        static::assertArrayHasKey('default', $config['tabs']);
        static::assertArrayHasKey('blocks', $config['tabs']['default']);
        static::assertEquals($expectedStructured, $config);
    }

    public function testAsyncCompilationIsSkippedWhenUsingStaticConfigLoader(): void
    {
        $themeId = Uuid::randomHex();
        $fs = new Filesystem(new InMemoryFilesystemAdapter());
        $fs->write(\sprintf('theme-config/%s.json', $themeId), (string) json_encode([
            'styleFiles' => [],
            'scriptFiles' => [],
        ]));
        $configLoader = new StaticFileConfigLoader($fs);

        $themeService = new ThemeService(
            $this->storefrontPluginRegistryMock,
            $this->themeRepositoryMock,
            $this->themeChannelRepositoryMock,
            $this->themeCompilerMock,
            $this->eventDispatcherMock,
            $configLoader,
            $this->connectionMock,
            $this->systemConfigMock,
            $this->messageBusMock,
            $this->createMock(NotificationService::class)
        );

        $this->systemConfigMock->expects(static::never())->method('get');
        $this->messageBusMock->expects(static::never())->method('dispatch');

        $this->themeCompilerMock->expects(static::once())->method('compileTheme')->with(
            TestDefaults::CHANNEL,
            $themeId,
            static::anything(),
            static::anything(),
            true,
            $this->context
        );

        $themeService->compileTheme(TestDefaults::CHANNEL, $themeId, $this->context);
    }

    /**
     * @return ThemeCollection
     */
    public static function getThemeCollectionForThemeConfiguration(): array
    {
        $themeId = Uuid::randomHex();
        $parentThemeId = Uuid::randomHex();
        $baseThemeId = Uuid::randomHex();

        return [
            [
                'ids' => [
                    'themeId' => $themeId,
                    'parentThemeId' => $parentThemeId,
                    'baseThemeId' => $baseThemeId,
                ],
                'themeCollection' => new ThemeCollection(
                    [
                        (new ThemeEntity())->assign(
                            [
                                'id' => $themeId,
                                '_uniqueIdentifier' => $themeId,
                                'channels' => new ChannelCollection(),
                                'technicalName' => 'Test',
                                'parentThemeId' => $parentThemeId,
                                'labels' => [
                                    'fields.extend-parent-custom-config' => 'EN',
                                ],
                                'helpTexts' => [
                                    'fields.extend-parent-custom-config' => 'EN Helptext',
                                ],
                                'baseConfig' => [
                                    'configInheritance' => [
                                        '@ParentTheme',
                                    ],
                                    'config' => ThemeFixtures::getThemeJsonConfig(),
                                    'fields' => [
                                        'extend-parent-custom-config' => [
                                            'type' => 'int',
                                            'label' => [
                                                'de-DE' => 'DE',
                                                'en-GB' => 'EN',
                                            ],
                                            'value' => '20',
                                            'editable' => true,
                                            'helpText' => [
                                                'de-DE' => 'De Helptext',
                                                'en-GB' => 'EN Helptext',
                                            ],
                                        ],
                                    ],
                                ],
                                'configValues' => [
                                    'test' => ['value' => ['no_test']],
                                ],
                            ]
                        ),
                        (new ThemeEntity())->assign(
                            [
                                'id' => $baseThemeId,
                                'technicalName' => FrontendPluginRegistry::BASE_THEME_NAME,
                                '_uniqueIdentifier' => $baseThemeId,
                            ]
                        ),
                        (new ThemeEntity())->assign(
                            [
                                'id' => $parentThemeId,
                                'technicalName' => 'ParentTheme',
                                'parentThemeId' => $baseThemeId,
                                '_uniqueIdentifier' => $parentThemeId,
                                'labels' => [
                                    'fields.parent-custom-config' => 'EN',
                                ],
                                'helpTexts' => [
                                    'fields.parent-custom-config' => 'EN Helptext',
                                ],
                                'baseConfig' => [
                                    'configInheritance' => [
                                        '@Frontend',
                                    ],
                                    'fields' => [
                                        'parent-custom-config' => [
                                            'type' => 'int',
                                            'label' => [
                                                'de-DE' => 'DE',
                                                'en-GB' => 'EN',
                                            ],
                                            'value' => '20',
                                            'editable' => true,
                                            'helpText' => [
                                                'de-DE' => 'De Helptext',
                                                'en-GB' => 'EN Helptext',
                                            ],
                                        ],
                                    ],
                                ],
                            ]
                        ),
                    ]
                ),
                'expected' => [
                    'blocks' => ThemeFixtures::getExtractedBlock1(),
                    'fields' => ThemeFixtures::getExtractedFields7(),
                    'configInheritance' => ThemeFixtures::getExtractedConfigInheritance(),
                    'config' => ThemeFixtures::getExtractedConfig1(),
                    'currentFields' => ThemeFixtures::getExtractedCurrentFields5(),
                    'baseThemeFields' => ThemeFixtures::getExtractedBaseThemeFields5(),
                ],
                'expectedNotTranslated' => [
                    'blocks' => ThemeFixtures::getExtractedBlock1(),
                    'fields' => ThemeFixtures::getExtractedFields8(),
                    'configInheritance' => ThemeFixtures::getExtractedConfigInheritance(),
                    'config' => ThemeFixtures::getExtractedConfig2(),
                    'currentFields' => ThemeFixtures::getExtractedCurrentFields5(),
                    'baseThemeFields' => ThemeFixtures::getExtractedBaseThemeFields5(),
                ],
                'expectedStructured' => [
                    'tabs' => ThemeFixtures::getExtractedTabs10(),
                ],
                'expectedStructuredNotTranslated' => [
                    'tabs' => ThemeFixtures::getExtractedTabs11(),
                ],
            ],
            [
                'ids' => [
                    'themeId' => $themeId,
                    'parentThemeId' => $parentThemeId,
                    'baseThemeId' => $baseThemeId,
                ],
                'themeCollection' => new ThemeCollection(
                    [
                        (new ThemeEntity())->assign(
                            [
                                'id' => $themeId,
                                '_uniqueIdentifier' => $themeId,
                                'channels' => new ChannelCollection(),
                                'technicalName' => 'Test',
                                'parentThemeId' => $parentThemeId,
                                'labels' => [
                                    'testlabel',
                                ],
                                'helpTexts' => [
                                    'testHelp',
                                ],
                                'baseConfig' => [
                                    'configInheritance' => [
                                        '@ParentTheme',
                                    ],
                                    'config' => ThemeFixtures::getThemeJsonConfig(),
                                ],
                                'configValues' => [
                                    'test' => ['value' => ['no_test']],
                                ],
                            ]
                        ),
                        (new ThemeEntity())->assign(
                            [
                                'id' => $baseThemeId,
                                'technicalName' => FrontendPluginRegistry::BASE_THEME_NAME,
                                '_uniqueIdentifier' => $baseThemeId,
                            ]
                        ),
                        (new ThemeEntity())->assign(
                            [
                                'id' => $parentThemeId,
                                'technicalName' => 'ParentTheme',
                                'parentThemeId' => $baseThemeId,
                                '_uniqueIdentifier' => $parentThemeId,
                            ]
                        ),
                    ]
                ),
                'expected' => [
                    'blocks' => ThemeFixtures::getExtractedBlock1(),
                    'fields' => ThemeFixtures::getExtractedFields1(),
                    'configInheritance' => ThemeFixtures::getExtractedConfigInheritance(),
                    'config' => ThemeFixtures::getExtractedConfig1(),
                    'currentFields' => ThemeFixtures::getExtractedCurrentFields1(),
                    'baseThemeFields' => ThemeFixtures::getExtractedBaseThemeFields1(),
                ],
                'expectedNotTranslated' => [
                    'blocks' => ThemeFixtures::getExtractedBlock1(),
                    'fields' => ThemeFixtures::getExtractedFields2(),
                    'configInheritance' => ThemeFixtures::getExtractedConfigInheritance(),
                    'config' => ThemeFixtures::getExtractedConfig2(),
                    'currentFields' => ThemeFixtures::getExtractedCurrentFields1(),
                    'baseThemeFields' => ThemeFixtures::getExtractedBaseThemeFields1(),
                ],
                'expectedStructured' => [
                    'tabs' => ThemeFixtures::getExtractedTabs1(),
                ],
                'expectedStructuredNotTranslated' => [
                    'tabs' => ThemeFixtures::getExtractedTabs2(),
                ],
            ],
            [
                'ids' => [
                    'themeId' => $themeId,
                    'parentThemeId' => $parentThemeId,
                    'baseThemeId' => $baseThemeId,
                ],
                'themeCollection' => new ThemeCollection(
                    [
                        (new ThemeEntity())->assign(
                            [
                                'id' => $themeId,
                                '_uniqueIdentifier' => $themeId,
                                'channels' => new ChannelCollection(),
                                'technicalName' => 'Test',
                                'parentThemeId' => $parentThemeId,
                                'labels' => [],
                                'helpTexts' => [
                                    'firstHelp',
                                    'testHelp',
                                ],
                                'baseConfig' => [
                                    'fields' => [
                                        'first' => [],
                                        'test' => [],
                                    ],
                                    'configInheritance' => [
                                        '@ParentTheme',
                                    ],
                                ],
                                'configValues' => [
                                    'test' => ['value' => ['no_test']],
                                ],
                            ]
                        ),
                        (new ThemeEntity())->assign(
                            [
                                'id' => $baseThemeId,
                                'technicalName' => FrontendPluginRegistry::BASE_THEME_NAME,
                                '_uniqueIdentifier' => $baseThemeId,
                            ]
                        ),
                        (new ThemeEntity())->assign(
                            [
                                'id' => $parentThemeId,
                                'technicalName' => 'ParentTheme',
                                'parentThemeId' => $baseThemeId,
                                '_uniqueIdentifier' => $parentThemeId,
                            ]
                        ),
                    ]
                ),
                'expected' => [
                    'blocks' => ThemeFixtures::getExtractedBlock1(),
                    'fields' => ThemeFixtures::getExtractedFields3(),
                    'configInheritance' => ThemeFixtures::getExtractedConfigInheritance(),
                    'currentFields' => ThemeFixtures::getExtractedCurrentFields2(),
                    'baseThemeFields' => ThemeFixtures::getExtractedBaseThemeFields2(),
                ],
                'expectedNotTranslated' => [
                    'blocks' => ThemeFixtures::getExtractedBlock1(),
                    'fields' => ThemeFixtures::getExtractedFields4(),
                    'configInheritance' => ThemeFixtures::getExtractedConfigInheritance(),
                    'currentFields' => ThemeFixtures::getExtractedCurrentFields2(),
                    'baseThemeFields' => ThemeFixtures::getExtractedBaseThemeFields2(),
                ],
                'expectedStructured' => [
                    'tabs' => ThemeFixtures::getExtractedTabs3(),
                ],
                'expectedStructuredNotTranslated' => [
                    'tabs' => ThemeFixtures::getExtractedTabs4(),
                ],
            ],
            [
                'ids' => [
                    'themeId' => $themeId,
                    'parentThemeId' => $parentThemeId,
                    'baseThemeId' => $baseThemeId,
                ],
                'themeCollection' => new ThemeCollection(
                    [
                        (new ThemeEntity())->assign(
                            [
                                'id' => $themeId,
                                '_uniqueIdentifier' => $themeId,
                                'channels' => new ChannelCollection(),
                                'parentThemeId' => $parentThemeId,
                                'configValues' => [
                                    'test' => ['value' => ['no_test']],
                                ],
                            ]
                        ),
                        (new ThemeEntity())->assign(
                            [
                                'id' => $baseThemeId,
                                'technicalName' => FrontendPluginRegistry::BASE_THEME_NAME,
                                '_uniqueIdentifier' => $baseThemeId,
                            ]
                        ),
                        (new ThemeEntity())->assign(
                            [
                                'id' => $parentThemeId,
                                'parentThemeId' => $baseThemeId,
                                '_uniqueIdentifier' => $parentThemeId,
                            ]
                        ),
                    ]
                ),
                'expected' => [
                    'fields' => ThemeFixtures::getExtractedFields5(),
                    'currentFields' => ThemeFixtures::getExtractedCurrentFields3(),
                    'baseThemeFields' => ThemeFixtures::getExtractedBaseThemeFields3(),
                ],
                'expectedNotTranslated' => null,
                'expectedStructured' => [
                    'tabs' => ThemeFixtures::getExtractedTabs5(),
                ],
                'expectedStructuredNotTranslated' => [
                    'tabs' => ThemeFixtures::getExtractedTabs6(),
                ],
            ],
            [
                'ids' => [
                    'themeId' => $themeId,
                    'parentThemeId' => $parentThemeId,
                    'baseThemeId' => $baseThemeId,
                ],
                'themeCollection' => new ThemeCollection(
                    [
                        (new ThemeEntity())->assign(
                            [
                                'id' => $themeId,
                                '_uniqueIdentifier' => $themeId,
                                'channels' => new ChannelCollection(),
                                'parentThemeId' => $parentThemeId,
                                'configValues' => [
                                    'test' => ['value' => ['no_test']],
                                ],
                            ]
                        ),
                        (new ThemeEntity())->assign(
                            [
                                'id' => $baseThemeId,
                                'technicalName' => FrontendPluginRegistry::BASE_THEME_NAME,
                                '_uniqueIdentifier' => $baseThemeId,
                            ]
                        ),
                        (new ThemeEntity())->assign(
                            [
                                'id' => $parentThemeId,
                                'parentThemeId' => $baseThemeId,
                                '_uniqueIdentifier' => $parentThemeId,
                                'baseConfig' => [
                                    'fields' => false,
                                ],
                            ]
                        ),
                    ]
                ),
                'expected' => [
                    'fields' => ThemeFixtures::getExtractedFields5(),
                    'currentFields' => ThemeFixtures::getExtractedCurrentFields3(),
                    'baseThemeFields' => ThemeFixtures::getExtractedBaseThemeFields3(),
                ],
                'expectedNotTranslated' => null,
                'expectedStructured' => [
                    'tabs' => ThemeFixtures::getExtractedTabs5(),
                ],
                'expectedStructuredNotTranslated' => [
                    'tabs' => ThemeFixtures::getExtractedTabs6(),
                ],
            ],
            [
                'ids' => [
                    'themeId' => $themeId,
                    'parentThemeId' => $parentThemeId,
                    'baseThemeId' => $baseThemeId,
                ],
                'themeCollection' => new ThemeCollection(
                    [
                        (new ThemeEntity())->assign(
                            [
                                'id' => $themeId,
                                '_uniqueIdentifier' => $themeId,
                                'channels' => new ChannelCollection(),
                                'parentThemeId' => $parentThemeId,
                                'configValues' => [
                                    'test' => ['value' => ['no_test']],
                                ],
                            ]
                        ),
                        (new ThemeEntity())->assign(
                            [
                                'id' => $baseThemeId,
                                'technicalName' => FrontendPluginRegistry::BASE_THEME_NAME,
                                '_uniqueIdentifier' => $baseThemeId,
                            ]
                        ),
                        (new ThemeEntity())->assign(
                            [
                                'id' => $parentThemeId,
                                'parentThemeId' => $baseThemeId,
                                '_uniqueIdentifier' => $parentThemeId,
                                'baseConfig' => [
                                    'fields' => [],
                                ],
                            ]
                        ),
                    ]
                ),
                'expected' => [
                    'fields' => ThemeFixtures::getExtractedFields5(),
                    'currentFields' => ThemeFixtures::getExtractedCurrentFields3(),
                    'baseThemeFields' => ThemeFixtures::getExtractedBaseThemeFields3(),
                ],
                'expectedNotTranslated' => null,
                'expectedStructured' => [
                    'tabs' => ThemeFixtures::getExtractedTabs5(),
                ],
                'expectedStructuredNotTranslated' => [
                    'tabs' => ThemeFixtures::getExtractedTabs6(),
                ],
            ],
            [
                'ids' => [
                    'themeId' => $themeId,
                    'parentThemeId' => $parentThemeId,
                    'baseThemeId' => $baseThemeId,
                ],
                'themeCollection' => new ThemeCollection(
                    [
                        (new ThemeEntity())->assign(
                            [
                                'id' => $themeId,
                                '_uniqueIdentifier' => $themeId,
                                'channels' => new ChannelCollection(),
                                'configValues' => [
                                    'test' => ['value' => ['no_test']],
                                ],
                            ]
                        ),
                        (new ThemeEntity())->assign(
                            [
                                'id' => $baseThemeId,
                                'technicalName' => FrontendPluginRegistry::BASE_THEME_NAME,
                                '_uniqueIdentifier' => $baseThemeId,
                            ]
                        ),
                    ]
                ),
                'expected' => [
                    'fields' => ThemeFixtures::getExtractedFields5(),
                    'currentFields' => ThemeFixtures::getExtractedCurrentFields3(),
                    'baseThemeFields' => ThemeFixtures::getExtractedBaseThemeFields3(),
                ],
                'expectedNotTranslated' => null,
                'expectedStructured' => [
                    'tabs' => ThemeFixtures::getExtractedTabs5(),
                ],
                'expectedStructuredNotTranslated' => [
                    'tabs' => ThemeFixtures::getExtractedTabs6(),
                ],
            ],
            [
                'ids' => [
                    'themeId' => $themeId,
                    'parentThemeId' => $parentThemeId,
                    'baseThemeId' => $baseThemeId,
                ],
                'themeCollection' => new ThemeCollection(
                    [
                        (new ThemeEntity())->assign(
                            [
                                'id' => $themeId,
                                '_uniqueIdentifier' => $themeId,
                                'channels' => new ChannelCollection(),
                                'configValues' => [],
                            ]
                        ),
                        (new ThemeEntity())->assign(
                            [
                                'id' => $baseThemeId,
                                'technicalName' => FrontendPluginRegistry::BASE_THEME_NAME,
                                '_uniqueIdentifier' => $baseThemeId,
                                'configValues' => [
                                    'test' => ['value' => ['no_test']],
                                ],
                            ]
                        ),
                    ]
                ),
                'expected' => [
                    'fields' => ThemeFixtures::getExtractedFields5(),
                    'currentFields' => ThemeFixtures::getExtractedBaseThemeFields3(),
                    'baseThemeFields' => ThemeFixtures::getExtractedCurrentFields3(),
                ],
                'expectedNotTranslated' => null,
                'expectedStructured' => [
                    'tabs' => ThemeFixtures::getExtractedTabs5(),
                ],
                'expectedStructuredNotTranslated' => [
                    'tabs' => ThemeFixtures::getExtractedTabs6(),
                ],
            ],
            [
                'ids' => [
                    'themeId' => $themeId,
                    'parentThemeId' => $parentThemeId,
                    'baseThemeId' => $baseThemeId,
                ],
                'themeCollection' => new ThemeCollection(
                    [
                        (new ThemeEntity())->assign(
                            [
                                'id' => $themeId,
                                '_uniqueIdentifier' => $themeId,
                                'channels' => new ChannelCollection(),
                                'baseConfig' => [
                                    'blocks' => ThemeFixtures::getExtractedBlocks2(),
                                    'tabs' => ThemeFixtures::getExtractedTabs7(),
                                    'section' => ThemeFixtures::getExtractedSections1(),
                                    'fields' => [
                                        'multi' => ThemeFixtures::getMultiSelectField(),
                                        'bool' => ThemeFixtures::getBoolField(),
                                    ],
                                ],
                            ]
                        ),
                        (new ThemeEntity())->assign(
                            [
                                'id' => $baseThemeId,
                                'technicalName' => FrontendPluginRegistry::BASE_THEME_NAME,
                                '_uniqueIdentifier' => $baseThemeId,
                                'configValues' => [
                                    'test' => ['value' => ['no_test']],
                                ],
                            ]
                        ),
                    ]
                ),
                'expected' => [
                    'fields' => ThemeFixtures::getExtractedFields6(),
                    'blocks' => ThemeFixtures::getExtractedBlocks2(),
                    'tabs' => ThemeFixtures::getExtractedTabs7(),
                    'section' => ThemeFixtures::getExtractedSections1(),
                    'currentFields' => ThemeFixtures::getExtractedCurrentFields4(),
                    'baseThemeFields' => ThemeFixtures::getExtractedBaseThemeFields4(),
                ],
                'expectedNotTranslated' => null,
                'expectedStructured' => [
                    'tabs' => ThemeFixtures::getExtractedTabs8(),
                ],
                'expectedStructuredNotTranslated' => [
                    'tabs' => ThemeFixtures::getExtractedTabs9(),
                ],
            ],
            [
                'ids' => [
                    'themeId' => $themeId,
                    'parentThemeId' => $parentThemeId,
                    'baseThemeId' => $baseThemeId,
                ],
                'themeCollection' => new ThemeCollection(
                    [
                        (new ThemeEntity())->assign(
                            [
                                'id' => $themeId,
                                'technicalName' => 'Theme',
                                '_uniqueIdentifier' => $themeId,
                                'baseConfig' => [
                                    'fields' => [
                                        'sw-color-brand-primary' => [
                                            'value' => '#adbd00',
                                        ],
                                    ],
                                ],
                            ]
                        ),
                        (new ThemeEntity())->assign(
                            [
                                'id' => $baseThemeId,
                                'technicalName' => FrontendPluginRegistry::BASE_THEME_NAME,
                                '_uniqueIdentifier' => $baseThemeId,
                                'baseConfig' => ThemeFixtures::getThemeJsonConfig(),
                                'labels' => [
                                    'blocks.media' => 'Media',
                                    'blocks.eCommerce' => 'E-Commerce',
                                    'blocks.unordered' => 'Misc',
                                    'blocks.typography' => 'Typography',
                                    'blocks.themeColors' => 'Theme colours',
                                    'blocks.statusColors' => 'Status messages',
                                    'fields.sw-color-info' => 'Information',
                                    'fields.sw-logo-share' => 'App & share icon',
                                    'fields.sw-text-color' => 'Text colour',
                                    'fields.sw-color-price' => 'Price',
                                    'fields.sw-logo-mobile' => 'Mobile',
                                    'fields.sw-logo-tablet' => 'Tablet',
                                    'fields.sw-border-color' => 'Border',
                                    'fields.sw-color-danger' => 'Error',
                                    'fields.sw-logo-desktop' => 'Desktop',
                                    'fields.sw-logo-favicon' => 'Favicon',
                                    'fields.sw-color-success' => 'Success',
                                    'fields.sw-color-warning' => 'Notice',
                                    'fields.sw-headline-color' => 'Headline colour',
                                    'fields.sw-background-color' => 'Background',
                                    'fields.sw-color-buy-button' => 'Buy button',
                                    'fields.sw-font-family-base' => 'Fonttype text',
                                    'fields.sw-color-brand-primary' => 'Primary colour',
                                    'fields.sw-font-family-headline' => 'Fonttype headline',
                                    'fields.sw-color-brand-secondary' => 'Secondary colour',
                                    'fields.sw-color-buy-button-text' => 'Buy button text',
                                ],
                                'helpTexts' => [
                                    'fields.sw-logo-mobile' => 'Displayed up to a viewport of 767px',
                                    'fields.sw-logo-tablet' => 'Displayed between a viewport of 767px to 991px',
                                    'fields.sw-logo-desktop' => 'Displayed on viewport sizes above 991px and as a fallback on smaller viewports, if no other logo is set.',
                                ],
                            ]
                        ),
                    ]
                ),
                'expected' => [
                    'blocks' => ThemeFixtures::getExtractedBlock1(),
                    'fields' => ThemeFixtures::getExtractedFields10(),
                    'currentFields' => ThemeFixtures::getExtractedCurrentFields6(),
                    'baseThemeFields' => ThemeFixtures::getExtractedBaseThemeFields6(),
                ],
                'expectedNotTranslated' => [
                    'blocks' => ThemeFixtures::getExtractedBlock1(),
                    'fields' => ThemeFixtures::getExtractedFields9(),
                    'currentFields' => ThemeFixtures::getExtractedCurrentFields6(),
                    'baseThemeFields' => ThemeFixtures::getExtractedBaseThemeFields6(),
                ],
                'expectedStructured' => [
                    'tabs' => ThemeFixtures::getExtractedTabs12(),
                ],
                'expectedStructuredNotTranslated' => [
                    'tabs' => ThemeFixtures::getExtractedTabs13(),
                ],
            ],
            [
                'ids' => [
                    'themeId' => $themeId,
                    'parentThemeId' => $parentThemeId,
                    'baseThemeId' => $baseThemeId,
                ],
                'themeCollection' => new ThemeCollection(
                    [
                        (new ThemeEntity())->assign(
                            [
                                'id' => $themeId,
                                '_uniqueIdentifier' => $themeId,
                                'channels' => new ChannelCollection(),
                                'parentThemeId' => $parentThemeId,
                                'baseConfig' => [
                                    'fields' => [
                                        'sw-color-brand-secondary' => [
                                            'value' => '#46801a',
                                        ],
                                    ],
                                ],
                            ]
                        ),
                        (new ThemeEntity())->assign(
                            [
                                'id' => $parentThemeId,
                                'technicalName' => 'Theme',
                                '_uniqueIdentifier' => $parentThemeId,
                                'baseConfig' => [
                                    'fields' => [
                                        'sw-color-brand-primary' => [
                                            'value' => '#adbd00',
                                        ],
                                    ],
                                ],
                            ]
                        ),
                        (new ThemeEntity())->assign(
                            [
                                'id' => $baseThemeId,
                                'technicalName' => FrontendPluginRegistry::BASE_THEME_NAME,
                                '_uniqueIdentifier' => $baseThemeId,
                                'baseConfig' => ThemeFixtures::getThemeJsonConfig(),
                                'labels' => [
                                    'blocks.media' => 'Media',
                                    'blocks.eCommerce' => 'E-Commerce',
                                    'blocks.unordered' => 'Misc',
                                    'blocks.typography' => 'Typography',
                                    'blocks.themeColors' => 'Theme colours',
                                    'blocks.statusColors' => 'Status messages',
                                    'fields.sw-color-info' => 'Information',
                                    'fields.sw-logo-share' => 'App & share icon',
                                    'fields.sw-text-color' => 'Text colour',
                                    'fields.sw-color-price' => 'Price',
                                    'fields.sw-logo-mobile' => 'Mobile',
                                    'fields.sw-logo-tablet' => 'Tablet',
                                    'fields.sw-border-color' => 'Border',
                                    'fields.sw-color-danger' => 'Error',
                                    'fields.sw-logo-desktop' => 'Desktop',
                                    'fields.sw-logo-favicon' => 'Favicon',
                                    'fields.sw-color-success' => 'Success',
                                    'fields.sw-color-warning' => 'Notice',
                                    'fields.sw-headline-color' => 'Headline colour',
                                    'fields.sw-background-color' => 'Background',
                                    'fields.sw-color-buy-button' => 'Buy button',
                                    'fields.sw-font-family-base' => 'Fonttype text',
                                    'fields.sw-color-brand-primary' => 'Primary colour',
                                    'fields.sw-font-family-headline' => 'Fonttype headline',
                                    'fields.sw-color-brand-secondary' => 'Secondary colour',
                                    'fields.sw-color-buy-button-text' => 'Buy button text',
                                ],
                                'helpTexts' => [
                                    'fields.sw-logo-mobile' => 'Displayed up to a viewport of 767px',
                                    'fields.sw-logo-tablet' => 'Displayed between a viewport of 767px to 991px',
                                    'fields.sw-logo-desktop' => 'Displayed on viewport sizes above 991px and as a fallback on smaller viewports, if no other logo is set.',
                                ],
                            ]
                        ),
                    ]
                ),
                'expected' => [
                    'blocks' => ThemeFixtures::getExtractedBlock1(),
                    'fields' => ThemeFixtures::getExtractedFields12(),
                    'currentFields' => ThemeFixtures::getExtractedCurrentFields7(),
                    'baseThemeFields' => ThemeFixtures::getExtractedBaseThemeFields7(),
                ],
                'expectedNotTranslated' => [
                    'blocks' => ThemeFixtures::getExtractedBlock1(),
                    'fields' => ThemeFixtures::getExtractedFields11(),
                    'currentFields' => ThemeFixtures::getExtractedCurrentFields7(),
                    'baseThemeFields' => ThemeFixtures::getExtractedBaseThemeFields7(),
                ],
                'expectedStructured' => [
                    'tabs' => ThemeFixtures::getExtractedTabs12(),
                ],
                'expectedStructuredNotTranslated' => [
                    'tabs' => ThemeFixtures::getExtractedTabs13(),
                ],
            ],
        ];
    }
}
