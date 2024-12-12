<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Currency\CurrencyCollection;
use Cicada\Core\System\Language\LanguageCollection;
use Cicada\Core\System\Channel\Aggregate\ChannelType\ChannelTypeCollection;

/**
 * @extends EntityCollection<ChannelEntity>
 */
#[Package('core')]
class ChannelCollection extends EntityCollection
{
    /**
     * @return array<string>
     */
    public function getLanguageIds(): array
    {
        return $this->fmap(fn (ChannelEntity $channel) => $channel->getLanguageId());
    }

    public function filterByLanguageId(string $id): ChannelCollection
    {
        return $this->filter(fn (ChannelEntity $channel) => $channel->getLanguageId() === $id);
    }

    /**
     * @return array<string>
     */
    public function getPaymentMethodIds(): array
    {
        return $this->fmap(fn (ChannelEntity $channel) => $channel->getPaymentMethodId());
    }


    /**
     * @return array<string>
     */
    public function getTypeIds(): array
    {
        return $this->fmap(fn (ChannelEntity $channel) => $channel->getTypeId());
    }

    public function filterByTypeId(string $id): ChannelCollection
    {
        return $this->filter(fn (ChannelEntity $channel) => $channel->getTypeId() === $id);
    }

    public function getLanguages(): LanguageCollection
    {
        return new LanguageCollection(
            $this->fmap(fn (ChannelEntity $channel) => $channel->getLanguage())
        );
    }

    public function getTypes(): ChannelTypeCollection
    {
        return new ChannelTypeCollection(
            $this->fmap(fn (ChannelEntity $channel) => $channel->getType())
        );
    }

    public function getApiAlias(): string
    {
        return 'channel_collection';
    }

    protected function getExpectedClass(): string
    {
        return ChannelEntity::class;
    }
}
