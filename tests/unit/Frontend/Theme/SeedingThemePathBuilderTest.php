<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Frontend\Theme;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Test\Stub\SystemConfigService\StaticSystemConfigService;
use Cicada\Core\Test\TestDefaults;
use Cicada\Frontend\Theme\SeedingThemePathBuilder;

/**
 * @internal
 */
#[CoversClass(SeedingThemePathBuilder::class)]
class SeedingThemePathBuilderTest extends TestCase
{
    public function testAssemblePathDoesNotChangeWithoutChangedSeed(): void
    {
        $pathBuilder = new SeedingThemePathBuilder(new StaticSystemConfigService());

        $path = $pathBuilder->assemblePath(TestDefaults::CHANNEL, 'theme');

        static::assertEquals($path, $pathBuilder->assemblePath(TestDefaults::CHANNEL, 'theme'));
    }

    public function testAssembledPathAfterSavingIsTheSameAsPreviouslyGenerated(): void
    {
        $pathBuilder = new SeedingThemePathBuilder(new StaticSystemConfigService());

        $generatedPath = $pathBuilder->generateNewPath(TestDefaults::CHANNEL, 'theme', 'foo');

        // assert seeding is taking into account when generating a new path
        static::assertNotEquals($generatedPath, $pathBuilder->assemblePath(TestDefaults::CHANNEL, 'theme'));

        $pathBuilder->saveSeed(TestDefaults::CHANNEL, 'theme', 'foo');

        // assert that the path is the same after saving
        static::assertEquals($generatedPath, $pathBuilder->assemblePath(TestDefaults::CHANNEL, 'theme'));
    }
}
