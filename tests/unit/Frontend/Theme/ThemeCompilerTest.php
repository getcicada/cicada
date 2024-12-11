<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Frontend\Theme;

use Cicada\Core\Framework\Adapter\Cache\CacheInvalidator;
use Cicada\Core\Framework\Adapter\Filesystem\Plugin\CopyBatchInput;
use Cicada\Core\Framework\Adapter\Filesystem\Plugin\CopyBatchInputFactory;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Test\TestCaseBase\EnvTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseHelper\ReflectionHelper;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\Stub\Framework\Util\StaticFilesystem;
use Cicada\Core\Test\TestDefaults;
use Cicada\Frontend\Event\ThemeCompilerConcatenatedStylesEvent;
use Cicada\Frontend\Theme\Event\ThemeCompilerEnrichScssVariablesEvent;
use Cicada\Frontend\Theme\Exception\ThemeCompileException;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\FileCollection;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\FrontendPluginConfiguration;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\FrontendPluginConfigurationCollection;
use Cicada\Frontend\Theme\MD5ThemePathBuilder;
use Cicada\Frontend\Theme\Message\DeleteThemeFilesMessage;
use Cicada\Frontend\Theme\ScssPhpCompiler;
use Cicada\Frontend\Theme\ThemeCompiler;
use Cicada\Frontend\Theme\ThemeFileResolver;
use Cicada\Frontend\Theme\ThemeFilesystemResolver;
use Cicada\Tests\Integration\Frontend\Theme\fixtures\MockThemeCompilerConcatenatedSubscriber;
use Cicada\Tests\Integration\Frontend\Theme\fixtures\MockThemeVariablesSubscriber;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\UrlPackage;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/**
 * @internal
 */
#[CoversClass(ThemeCompiler::class)]
class ThemeCompilerTest extends TestCase
{
    use EnvTestBehaviour;

    private string $mockChannelId;

    /**
     * @var ThemeFileResolver&MockObject
     */
    private ThemeFileResolver $themeFileResolver;

    private Filesystem $filesystem;

    private Filesystem $tempFilesystem;

    /**
     * @var EventDispatcher&MockObject
     */
    private EventDispatcher $eventDispatcher;

    /**
     * @var CacheInvalidator&MockObject
     */
    private CacheInvalidator $cacheInvalidator;

    /**
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $logger;

    /**
     * @var ScssPhpCompiler&MockObject
     */
    private ScssPhpCompiler $scssPhpCompiler;

    private MD5ThemePathBuilder $pathBuilder;

    private MessageBus $messageBus;

    private ThemeFilesystemResolver&MockObject $themeFilesystemResolver;

    /**
     * @var CopyBatchInputFactory&MockObject
     */
    private CopyBatchInputFactory $copyBatchInputFactory;

    protected function setUp(): void
    {
        $this->themeFileResolver = $this->createMock(ThemeFileResolver::class);
        $this->eventDispatcher = $this->createMock(EventDispatcher::class);
        $this->cacheInvalidator = $this->createMock(CacheInvalidator::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->scssPhpCompiler = $this->createMock(ScssPhpCompiler::class);
        $this->pathBuilder = new MD5ThemePathBuilder();
        $this->messageBus = new MessageBus();
        $this->copyBatchInputFactory = $this->createMock(CopyBatchInputFactory::class);
        $this->themeFilesystemResolver = $this->createMock(ThemeFilesystemResolver::class);

        $this->filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $this->tempFilesystem = new Filesystem(new InMemoryFilesystemAdapter());

        $this->mockChannelId = '98432def39fc4624b33213a56b8c944d';
    }

    public function testThemeCompileExceptionIsThrownWhenConcatenateFails(): void
    {
        $this->themeFileResolver->method('resolveFiles')->willReturn(
            [ThemeFileResolver::STYLE_FILES => FileCollection::createFromArray(['foo'])]
        );

        $this->eventDispatcher->method('dispatch')->willThrowException(new \Exception());

        $compiler = $this->getThemeCompiler();

        $config = new FrontendPluginConfiguration('test');
        $config->setName('faultyTheme');

        static::expectExceptionObject(new ThemeCompileException('faultyTheme'));
        $compiler->compileTheme(
            TestDefaults::CHANNEL,
            'test',
            $config,
            new FrontendPluginConfigurationCollection(),
            true,
            Context::createDefaultContext()
        );
    }

    public function testThemeCompileExceptionIsThrownWhenCollectCompiledFilesFails(): void
    {
        $this->themeFileResolver->method('resolveFiles')->willReturn(
            [ThemeFileResolver::STYLE_FILES => FileCollection::createFromArray(['foo'])]
        );

        $this->copyBatchInputFactory->method('fromDirectory')->willThrowException(new \Exception());

        $compiler = $this->getThemeCompiler();

        $config = new FrontendPluginConfiguration('test');
        $config->setName('faultyTheme');
        $config->setAssetPaths(['bla']);

        static::expectExceptionObject(new ThemeCompileException('faultyTheme'));
        $compiler->compileTheme(
            TestDefaults::CHANNEL,
            'test',
            $config,
            new FrontendPluginConfigurationCollection(),
            true,
            Context::createDefaultContext()
        );
    }

    public function testFormatVariablesArrayConvertsToNonAssociativeArrayWithValidScssSyntax(): void
    {
        $formatVariables = ReflectionHelper::getMethod(ThemeCompiler::class, 'formatVariables');

        $variables = [
            'sw-color-brand-primary' => '#008490',
            'sw-color-brand-secondary' => '#526e7f',
            'sw-border-color' => '#bcc1c7',
        ];

        $actual = $formatVariables->invoke($this->getThemeCompiler(), $variables);

        $expected = [
            '$sw-color-brand-primary: #008490;',
            '$sw-color-brand-secondary: #526e7f;',
            '$sw-border-color: #bcc1c7;',
        ];

        static::assertSame($expected, $actual);
    }

    /**
     * @param array<string> $config
     */
    #[DataProvider('configForDumpVariables')]
    public function testDumpVariables(array $config, string $expected): void
    {
        $dumpVariables = ReflectionHelper::getMethod(ThemeCompiler::class, 'dumpVariables');

        $actual = $dumpVariables->invoke($this->getThemeCompiler(), $config, 'themeId', $this->mockChannelId, Context::createDefaultContext());

        static::assertSame($expected, $actual);
    }

    public static function configForDumpVariables(): \Generator
    {
        // The resulting color values will be #ffffff00 because the scsscompiler is just a mock and the fallback will replace the real values
        yield 'finds config fields and returns string with scss variables' => [
            [
                'fields' => [
                    'sw-color-brand-primary' => [
                        'name' => 'sw-color-brand-primary',
                        'type' => 'color',
                        'value' => '#008490',
                    ],
                    'sw-color-brand-secondary' => [
                        'name' => 'sw-color-brand-secondary',
                        'type' => 'color',
                        'value' => '#526e7f',
                    ],
                    'sw-border-color' => [
                        'name' => 'sw-border-color',
                        'type' => 'color',
                        'value' => '#bcc1c7',
                    ],
                    'sw-custom-header' => [
                        'name' => 'sw-custom-header',
                        'type' => 'checkbox',
                        'value' => false,
                    ],
                    'sw-custom-footer' => [
                        'name' => 'sw-custom-header',
                        'type' => 'checkbox',
                        'value' => true,
                    ],
                    'sw-custom-cart' => [
                        'name' => 'sw-custom-header',
                        'type' => 'switch',
                        'value' => false,
                    ],
                    'sw-custom-product-box' => [
                        'name' => 'sw-custom-header',
                        'type' => 'switch',
                        'value' => true,
                    ],
                    'sw-custom-textarea' => [
                        'name' => 'sw-custom-textarea',
                        'type' => 'textarea',
                        'value' => '123',
                    ],
                    'sw-invalid-textarea' => [
                        'name' => 'sw-invalid-textarea',
                        'type' => 'media',
                        'value' => [123],
                    ],
                    'sw-custom-media' => [
                        'name' => 'sw-custom-media',
                        'type' => 'media',
                        'value' => '456',
                    ],
                    'sw-invalid-media' => [
                        'name' => 'sw-invalid-media',
                        'type' => 'media',
                        'value' => [false],
                    ],
                    'sw-invalid-type' => [
                        'name' => 'sw-invalid-type',
                        'value' => [false],
                    ],
                    'sw-multi-test' => [
                        'name' => 'sw-multi-test',
                        'type' => 'text',
                        'value' => [
                            'top',
                            'bottom',
                        ],
                        'custom' => [
                            'componentName' => 'sw-multi-select',
                            'options' => [
                                [
                                    'value' => 'bottom',
                                ],
                                [
                                    'value' => 'top',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            <<<PHP_EOL
// ATTENTION! This file is auto generated by the Cicada\Frontend\Theme\ThemeCompiler and should not be edited.

\$theme-id: themeId;
\$sw-color-brand-primary: #008490;
\$sw-color-brand-secondary: #526e7f;
\$sw-border-color: #bcc1c7;
\$sw-custom-header: 0;
\$sw-custom-footer: 1;
\$sw-custom-cart: 0;
\$sw-custom-product-box: 1;
\$sw-custom-textarea: '123';
\$sw-custom-media: '456';
\$sw-asset-theme-url: 'http://localhost';

PHP_EOL,
        ];

        yield 'ignores fields with scss config property set to false' => [
            [
                'fields' => [
                    'sw-color-brand-primary' => [
                        'name' => 'sw-color-brand-primary',
                        'type' => 'color',
                        'value' => '#008490',
                    ],
                    'sw-color-brand-secondary' => [
                        'name' => 'sw-color-brand-secondary',
                        'type' => 'color',
                        'value' => '#526e7f',
                    ],
                    // Prevent adding field as sass variable
                    'sw-ignore-me' => [
                        'name' => 'sw-border-color',
                        'type' => 'text',
                        'value' => 'Foo bar',
                        'scss' => false,
                    ],
                ],
            ],
            <<<PHP_EOL
// ATTENTION! This file is auto generated by the Cicada\Frontend\Theme\ThemeCompiler and should not be edited.

\$theme-id: themeId;
\$sw-color-brand-primary: #008490;
\$sw-color-brand-secondary: #526e7f;
\$sw-asset-theme-url: 'http://localhost';

PHP_EOL,
        ];
        yield 'HasNoConfigFieldsAndReturnsOnlyDefaultVariables' => [
            [
                'blocks' => [
                    'themeColors' => [
                        'label' => [
                            'en-GB' => 'Theme colours',
                            'de-DE' => 'Theme-Farben',
                        ],
                    ],
                    'typography' => [
                        'label' => [
                            'en-GB' => 'Typography',
                            'de-DE' => 'Typografie',
                        ],
                    ],
                ],
            ],
            '// ATTENTION! This file is auto generated by the Cicada\Frontend\Theme\ThemeCompiler and should not be edited.

$theme-id: themeId;
$sw-asset-theme-url: \'http://localhost\';
',
        ];
        yield 'MayHaveZeroValueButNotNull' => [
            [
                'fields' => [
                    'sw-zero-margin' => [
                        'name' => 'sw-null-margin',
                        'type' => 'text',
                        'value' => 0,
                    ],
                    'sw-null-margin' => [
                        'name' => 'sw-null-margin',
                        'type' => 'text',
                        'value' => null,
                    ],
                    'sw-unset-margin' => [
                        'name' => 'sw-unset-margin',
                        'type' => 'text',
                    ],
                    'sw-empty-margin' => [
                        'name' => 'sw-unset-margin',
                        'type' => 'text',
                        'value' => '',
                    ],
                ],
            ],
            <<<PHP_EOL
// ATTENTION! This file is auto generated by the Cicada\Frontend\Theme\ThemeCompiler and should not be edited.

\$theme-id: themeId;
\$sw-zero-margin: 0;
\$sw-null-margin: 0;
\$sw-unset-margin: 0;
\$sw-empty-margin: 0;
\$sw-asset-theme-url: 'http://localhost';

PHP_EOL,
        ];
    }

    public function testScssVariablesEventAddsNewVariablesToArray(): void
    {
        $subscriber = new MockThemeVariablesSubscriber($this->createMock(SystemConfigService::class));

        $variables = [
            'sw-color-brand-primary' => '#008490',
            'sw-color-brand-secondary' => '#526e7f',
            'sw-border-color' => '#bcc1c7',
        ];

        $event = new ThemeCompilerEnrichScssVariablesEvent($variables, $this->mockChannelId, Context::createDefaultContext());
        $subscriber->onAddVariables($event);

        $actual = $event->getVariables();

        $expected = [
            'sw-color-brand-primary' => '#008490',
            'sw-color-brand-secondary' => '#526e7f',
            'sw-border-color' => '#bcc1c7',
            'mock-variable-black' => '#000000',
            'mock-variable-special' => '\'Special value with quotes\'',
        ];

        static::assertSame($expected, $actual);
    }

    public function testConcatenatedStylesEventPassThru(): void
    {
        $subscriber = new MockThemeCompilerConcatenatedSubscriber();

        $styles = 'body {}';

        $event = new ThemeCompilerConcatenatedStylesEvent($styles, $this->mockChannelId);
        $subscriber->onGetConcatenatedStyles($event);
        $actual = $event->getConcatenatedStyles();

        $expected = $styles . MockThemeCompilerConcatenatedSubscriber::STYLES_CONCAT;

        static::assertEquals($expected, $actual);
    }

    public function testCompileWithoutAssets(): void
    {
        $this->themeFileResolver->method('resolveFiles')->willReturn([
            ThemeFileResolver::SCRIPT_FILES => new FileCollection(),
            ThemeFileResolver::STYLE_FILES => new FileCollection(),
        ]);

        $compiler = $this->getThemeCompiler();

        $config = new FrontendPluginConfiguration('test');
        $config->setAssetPaths(['bla']);

        $pathBuilder = new MD5ThemePathBuilder();
        static::assertEquals('9a11a759d278b4a55cb5e2c3414733c1', $pathBuilder->assemblePath(TestDefaults::CHANNEL, 'test'));

        try {
            $pathBuilder->getDecorated();
        } catch (\Throwable $e) {
            static::assertInstanceOf(DecorationPatternException::class, $e);
        }

        $compiler->compileTheme(
            TestDefaults::CHANNEL,
            'test',
            $config,
            new FrontendPluginConfigurationCollection(),
            false,
            Context::createDefaultContext()
        );

        static::assertTrue($this->filesystem->has('theme/9a11a759d278b4a55cb5e2c3414733c1'));
    }

    public function testAssetPathWillBeAbsoluteConverted(): void
    {
        $config = new FrontendPluginConfiguration('test');
        $config->setAssetPaths(['assets']);

        $fs = new StaticFilesystem(['Resources/assets' => 'directory']);

        $this->themeFilesystemResolver->expects(static::once())
            ->method('getFilesystemForFrontendConfig')
            ->with($config)
            ->willReturn($fs);

        $this->themeFileResolver->method('resolveFiles')->willReturn([
            ThemeFileResolver::SCRIPT_FILES => new FileCollection(),
            ThemeFileResolver::STYLE_FILES => new FileCollection(),
        ]);

        $this->filesystem->createDirectory('temp');
        $this->filesystem->write('temp/test.png', '');
        $png = $this->filesystem->readStream('temp/test.png');

        $this->copyBatchInputFactory->method('fromDirectory')->with('/app-root/Resources/assets', 'theme/test')->willReturn(
            [
                new CopyBatchInput($png, ['theme/9a11a759d278b4a55cb5e2c3414733c1/assets/test.png']),
            ]
        );

        $compiler = $this->getThemeCompiler();

        $pathBuilder = new MD5ThemePathBuilder();
        static::assertEquals('9a11a759d278b4a55cb5e2c3414733c1', $pathBuilder->assemblePath(TestDefaults::CHANNEL, 'test'));

        try {
            $pathBuilder->getDecorated();
        } catch (\Throwable $e) {
            static::assertInstanceOf(DecorationPatternException::class, $e);
        }

        $compiler->compileTheme(
            TestDefaults::CHANNEL,
            'test',
            $config,
            new FrontendPluginConfigurationCollection(),
            true,
            Context::createDefaultContext()
        );

        static::assertTrue($this->filesystem->fileExists('theme/9a11a759d278b4a55cb5e2c3414733c1/assets/test.png'));
    }

    public function testExistingFilesAreNotDeletedOnCompileError(): void
    {
        $this->themeFileResolver->method('resolveFiles')->willReturn(
            [
                ThemeFileResolver::SCRIPT_FILES => new FileCollection(),
                ThemeFileResolver::STYLE_FILES => new FileCollection()]
        );

        $this->filesystem->createDirectory('theme/9a11a759d278b4a55cb5e2c3414733c1');
        $this->filesystem->write('theme/9a11a759d278b4a55cb5e2c3414733c1/all.js', '');

        $this->scssPhpCompiler->expects(static::once())->method('compileString')->willThrowException(new \Exception());

        $compiler = $this->getThemeCompiler();

        $config = new FrontendPluginConfiguration('test');
        $config->setAssetPaths(['assets']);

        $pathBuilder = new MD5ThemePathBuilder();
        static::assertEquals('9a11a759d278b4a55cb5e2c3414733c1', $pathBuilder->assemblePath(TestDefaults::CHANNEL, 'test'));

        $wasThrown = false;

        try {
            $compiler->compileTheme(
                TestDefaults::CHANNEL,
                'test',
                $config,
                new FrontendPluginConfigurationCollection(),
                true,
                Context::createDefaultContext()
            );
        } catch (ThemeCompileException) {
            $wasThrown = true;
        }

        static::assertTrue($wasThrown);
        static::assertTrue($this->filesystem->fileExists('theme/9a11a759d278b4a55cb5e2c3414733c1/all.js'));
    }

    public function testNewFilesAreDeletedOnCompileError(): void
    {
        $this->themeFileResolver->method('resolveFiles')->willReturn(
            [
                ThemeFileResolver::SCRIPT_FILES => new FileCollection(),
                ThemeFileResolver::STYLE_FILES => new FileCollection()]
        );

        $this->filesystem->createDirectory('theme/current');
        $this->filesystem->write('theme/current/all.js', '');

        $this->copyBatchInputFactory->expects(static::never())
            ->method('fromDirectory');

        $this->scssPhpCompiler->expects(static::once())->method('compileString')->willThrowException(new \Exception());

        $this->pathBuilder = $this->createMock(MD5ThemePathBuilder::class);
        $this->pathBuilder->method('assemblePath')->willReturn('current');
        $this->pathBuilder->method('generateNewPath')->willReturn('new');
        $this->pathBuilder->expects(static::never())->method('saveSeed');

        $compiler = $this->getThemeCompiler();

        $config = new FrontendPluginConfiguration('test');
        $config->setAssetPaths(['assets']);

        $wasThrown = false;

        try {
            $compiler->compileTheme(
                TestDefaults::CHANNEL,
                'test',
                $config,
                new FrontendPluginConfigurationCollection(),
                true,
                Context::createDefaultContext()
            );
        } catch (ThemeCompileException) {
            $wasThrown = true;
        }

        static::assertTrue($wasThrown);
        static::assertTrue($this->filesystem->fileExists('theme/current/all.js'));
        static::assertFalse($this->filesystem->fileExists('theme/new/all.js'));
    }

    public function testOldThemeFilesAreDeletedDelayedOnThemeCompileSuccess(): void
    {
        $this->themeFileResolver->method('resolveFiles')->willReturn(
            [
                ThemeFileResolver::SCRIPT_FILES => new FileCollection(),
                ThemeFileResolver::STYLE_FILES => new FileCollection()]
        );

        $this->filesystem->createDirectory('theme/current');
        $this->filesystem->write('theme/current/all.js', '');

        $this->scssPhpCompiler->expects(static::once())->method('compileString')->willReturn('');

        $this->pathBuilder = $this->createMock(MD5ThemePathBuilder::class);
        $this->pathBuilder->method('assemblePath')->willReturn('current');
        $this->pathBuilder->expects(static::once())
            ->method('generateNewPath')
            ->with(
                TestDefaults::CHANNEL,
                'test'
            )
            ->willReturn('new');
        $this->pathBuilder->expects(static::once())
            ->method('saveSeed')
            ->with(TestDefaults::CHANNEL, 'test');

        $expectedMessage = new DeleteThemeFilesMessage('current', TestDefaults::CHANNEL, 'test');
        $expectedStamps = [new DelayStamp(900000)];

        $expectedEnvelop = new Envelope($expectedMessage, $expectedStamps);

        $this->messageBus = $this->createMock(MessageBus::class);
        $this->messageBus->expects(static::once())
            ->method('dispatch')
            ->with($expectedMessage, $expectedStamps)
            ->willReturn($expectedEnvelop);

        $compiler = $this->getThemeCompiler(900);

        $config = new FrontendPluginConfiguration('test');
        $config->setAssetPaths(['assets']);

        $compiler->compileTheme(
            TestDefaults::CHANNEL,
            'test',
            $config,
            new FrontendPluginConfigurationCollection(),
            true,
            Context::createDefaultContext()
        );

        static::assertTrue($this->filesystem->fileExists('theme/current/all.js'));
    }

    /**
     * @param array<string> $mappings
     */
    #[DataProvider('importPathsProvider')]
    public function testGetResolveImportPathsCallbackReturnsNull(array $mappings, string $originPath): void
    {
        $compiler = $this->getThemeCompiler();
        $closure = $compiler->getResolveImportPathsCallback($mappings);

        static::assertNull($closure($originPath));
    }

    public static function importPathsProvider(): \Generator
    {
        yield 'no mapping' => [
            [],
            'fake_path',
        ];
        yield 'wrong path without extension' => [
            ['fake_path' => 'fake_path'],
            '~fake_path',
        ];
        yield 'wrong path with min extension' => [
            ['fake_path' => 'fake_path'],
            '~fake_path.min',
        ];
        yield 'wrong path with zip extension' => [
            ['fake_path' => 'fake_path'],
            '~fake_path.zip',
        ];
    }

    protected function getThemeCompiler(int $themeFileDeleteDelay = 0): ThemeCompiler
    {
        return new ThemeCompiler(
            $this->filesystem,
            $this->tempFilesystem,
            $this->copyBatchInputFactory,
            $this->themeFileResolver,
            true,
            $this->eventDispatcher,
            $this->themeFilesystemResolver,
            ['theme' => new UrlPackage(['http://localhost'], new EmptyVersionStrategy())],
            $this->cacheInvalidator,
            $this->logger,
            $this->pathBuilder,
            $this->scssPhpCompiler,
            $this->messageBus,
            $themeFileDeleteDelay,
            false
        );
    }
}