<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Aggregate\ChannelType;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelCollection;

/**
 * @extends EntityCollection<ChannelTypeEntity>
 */
#[Package('frontend')]
class ChannelTypeCollection extends EntityCollection
{
    public function getChannels(): ChannelCollection
    {
        return new ChannelCollection(
            $this->fmap(fn (ChannelTypeEntity $channel) => $channel->getChannels())
        );
    }

    public function getApiAlias(): string
    {
        return 'channel_type_collection';
    }

    protected function getExpectedClass(): string
    {
        return ChannelTypeEntity::class;
    }
}
