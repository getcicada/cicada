<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Aggregate\ChannelAnalytics;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<ChannelAnalyticsEntity>
 */
#[Package('frontend')]
class ChannelAnalyticsCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'channel_analytics_collection';
    }

    protected function getExpectedClass(): string
    {
        return ChannelAnalyticsEntity::class;
    }
}
