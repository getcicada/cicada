<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Util\Hasher;
use Cicada\Core\System\SystemConfig\SystemConfigService;

#[Package('frontend')]
class SeedingThemePathBuilder extends AbstractThemePathBuilder
{
    private const SYSTEM_CONFIG_KEY = 'frontend.themeSeed';

    /**
     * @internal
     */
    public function __construct(
        private readonly SystemConfigService $systemConfigService,
    ) {
    }

    public function assemblePath(string $channelId, string $themeId): string
    {
        return $this->generateNewPath($channelId, $themeId, $this->getSeed($channelId));
    }

    public function generateNewPath(string $channelId, string $themeId, string $seed): string
    {
        return Hasher::hash($themeId . $channelId . $seed);
    }

    public function saveSeed(string $channelId, string $themeId, string $seed): void
    {
        $this->systemConfigService->set(self::SYSTEM_CONFIG_KEY, $seed, $channelId);
    }

    public function getDecorated(): AbstractThemePathBuilder
    {
        throw new DecorationPatternException(self::class);
    }

    private function getSeed(string $channelId): string
    {
        /** @var string|null $seed */
        $seed = $this->systemConfigService->get(self::SYSTEM_CONFIG_KEY, $channelId);

        if (!$seed) {
            return '';
        }

        return $seed;
    }
}
