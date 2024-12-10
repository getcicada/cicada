<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Util\Hasher;

/**
 * ThemePathBuilder that does not support seeding,
 * this should only be used in projects where recompiling the theme at runtime is not supported (e.g. PaaS) or for testing.
 */
#[Package('frontend')]
class MD5ThemePathBuilder extends AbstractThemePathBuilder
{
    public function assemblePath(string $channelId, string $themeId): string
    {
        return Hasher::hash($themeId . $channelId, 'md5');
    }

    public function generateNewPath(string $channelId, string $themeId, string $seed): string
    {
        return $this->assemblePath($channelId, $themeId);
    }

    public function saveSeed(string $channelId, string $themeId, string $seed): void
    {
        // do nothing, as this implementation does not support seeding
    }

    public function getDecorated(): AbstractThemePathBuilder
    {
        throw new DecorationPatternException(self::class);
    }
}
