<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme;

use Doctrine\DBAL\Connection;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @deprecated tag:v6.7.0 - will be removed. Use DatabaseChannelThemeLoader instead
 */
#[Package('frontend')]
class ChannelThemeLoader implements ResetInterface
{
    /**
     * @var array<string, array{themeId?: string, themeName?: string, parentThemeName?: string}>
     */
    private array $themes = [];

    /**
     * @internal
     */
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @return array{themeId?: string, themeName?: string, parentThemeName?: string, grandParentNames?: array<string, mixed>}
     */
    public function load(string $channelId): array
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedMethodMessage(self::class, __METHOD__, 'v6.7.0.0')
        );

        if (!empty($this->themes[$channelId])) {
            return $this->themes[$channelId];
        }

        $themes = $this->connection->fetchAssociative('
            SELECT LOWER(HEX(theme.id)) themeId, theme.technical_name as themeName, parentTheme.technical_name as parentThemeName, LOWER(HEX(parentTheme.parent_theme_id)) as grandParentThemeId
            FROM channel
                LEFT JOIN theme_channel ON channel.id = theme_channel.channel_id
                LEFT JOIN theme ON theme_channel.theme_id = theme.id
                LEFT JOIN theme AS parentTheme ON parentTheme.id = theme.parent_theme_id
            WHERE channel.id = :channelId
        ', [
            'channelId' => Uuid::fromHexToBytes($channelId),
        ]);

        if (\is_array($themes) && isset($themes['grandParentThemeId']) && \is_string($themes['grandParentThemeId'])) {
            $themes['grandParentNames'] = $this->getGrandParents($themes['grandParentThemeId']);
        }

        return $this->themes[$channelId] = $themes ?: [];
    }

    public function reset(): void
    {
        if (!Feature::isActive('v6.7.0.0')) { // reset interface does not work with triggerDeprecation
            Feature::triggerDeprecationOrThrow(
                'v6.7.0.0',
                Feature::deprecatedMethodMessage(self::class, __METHOD__, 'v6.7.0.0')
            );
        }

        $this->themes = [];
    }

    /**
     * @return array<int, string>
     */
    private function getGrandParents(mixed $grandParentThemeId): array
    {
        $grandParents = $this->connection->fetchAssociative('
            SELECT theme.technical_name as themeName, parentTheme.technical_name as parentThemeName, LOWER(HEX(parentTheme.parent_theme_id)) as grandParentThemeId
            FROM theme
                LEFT JOIN theme AS parentTheme ON parentTheme.id = theme.parent_theme_id
            WHERE theme.id = :id
        ', [
            'id' => Uuid::fromHexToBytes($grandParentThemeId),
        ]);

        $filtered = array_filter([
            $grandParents['themeName'] ?? null,
            $grandParents['parentThemeName'] ?? null,
        ]);

        if (\is_array($grandParents) && isset($grandParents['grandParentThemeId']) && \is_string($grandParents['grandParentThemeId'])) {
            $filtered = array_merge($filtered, $this->getGrandParents($grandParents['grandParentThemeId']));
        }

        return $filtered;
    }
}
