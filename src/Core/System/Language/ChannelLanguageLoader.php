<?php declare(strict_types=1);

namespace Cicada\Core\System\Language;

use Doctrine\DBAL\Connection;
use Cicada\Core\Framework\DataAbstractionLayer\Doctrine\FetchModeHelper;
use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal
 */
#[Package('core')]
class ChannelLanguageLoader implements ResetInterface
{
    /**
     * @var array<string, array<string>>|null
     */
    private ?array $languages = null;

    /**
     * @internal
     */
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @return array<string, array<string>>
     */
    public function loadLanguages(): array
    {
        if ($this->languages) {
            return $this->languages;
        }

        $result = $this->connection->fetchAllAssociative('SELECT LOWER(HEX(`language_id`)), LOWER(HEX(`channel_id`)) as channelId FROM channel_language');

        /** @var array<string, array{ channelId: string }> $grouped */
        $grouped = FetchModeHelper::group($result);

        foreach ($grouped as $languageId => $value) {
            $grouped[$languageId] = array_column($value, 'channelId');
        }

        return $this->languages = $grouped;
    }

    public function reset(): void
    {
        $this->languages = null;
    }
}
