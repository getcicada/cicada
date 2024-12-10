<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Aggregate\ChannelDomain;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<ChannelDomainEntity>
 */
#[Package('frontend')]
class ChannelDomainCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'channel_domain_collection';
    }

    protected function getExpectedClass(): string
    {
        return ChannelDomainEntity::class;
    }
}
