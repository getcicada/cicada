<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Util;

use Cicada\Core\DevOps\Environment\EnvironmentHelper;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Kernel;

/**
 * @internal
 */
#[Package('core')]
class VersionParser
{
    /**
     * @var string Regex pattern for validating Cicada versions
     */
    private const VALID_VERSION_PATTERN = '#^\d\.\d+\.\d+\.(\d+|x)(-\w+)?#';

    /**
     * @return array{version: string, revision: string|null}
     */
    public static function parseCicadaVersion(?string $version): array
    {
        /**
         * The SW_FAKE_VERSION environment variable can be used to fake the current version of cicada
         * It's useful to simulate an update to a version which might not exist, or to perform an update without running migrations, etc.
         * eg: SW_FAKE_VERSION=6.7.0.3 bin/console system:update:finish
         **/
        $fallbackVersion = (string) EnvironmentHelper::getVariable('SW_FAKE_VERSION', Kernel::CICADA_FALLBACK_VERSION);

        // does not come from composer, was set manually
        if ($version === null || mb_strpos($version, '@') === false) {
            return [
                'version' => $fallbackVersion,
                'revision' => str_repeat('0', 32),
            ];
        }

        [$version, $hash] = explode('@', $version);
        $version = ltrim($version, 'v');
        $version = str_replace('+', '-', $version);

        /*
         * checks if the version is a valid version pattern
         * \Cicada\Tests\Unit\Core\Framework\Util\VersionParserTest::testParseCicadaVersion
         */
        if (!preg_match(self::VALID_VERSION_PATTERN, $version)) {
            $version = $fallbackVersion;
        }

        return [
            'version' => $version,
            'revision' => $hash,
        ];
    }
}