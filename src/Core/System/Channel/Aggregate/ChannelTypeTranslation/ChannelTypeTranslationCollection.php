<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Aggregate\ChannelTypeTranslation;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<ChannelTypeTranslationEntity>
 */
#[Package('frontend')]
class ChannelTypeTranslationCollection extends EntityCollection
{
    /**
     * @return array<string>
     */
    public function getChannelTypeIds(): array
    {
        return $this->fmap(fn (ChannelTypeTranslationEntity $channelTypeTranslation) => $channelTypeTranslation->getChannelTypeId());
    }

    public function filterByChannelId(string $id): self
    {
        return $this->filter(fn (ChannelTypeTranslationEntity $channelTypeTranslation) => $channelTypeTranslation->getChannelTypeId() === $id);
    }

    /**
     * @return array<string>
     */
    public function getLanguageIds(): array
    {
        return $this->fmap(fn (ChannelTypeTranslationEntity $channelTranslation) => $channelTranslation->getLanguageId());
    }

    public function filterByLanguageId(string $id): self
    {
        return $this->filter(fn (ChannelTypeTranslationEntity $channelTranslation) => $channelTranslation->getLanguageId() === $id);
    }

    public function getApiAlias(): string
    {
        return 'channel_type_translation_collection';
    }

    protected function getExpectedClass(): string
    {
        return ChannelTypeTranslationEntity::class;
    }
}
