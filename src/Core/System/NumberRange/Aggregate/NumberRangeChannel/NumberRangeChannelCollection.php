<?php declare(strict_types=1);

namespace Cicada\Core\System\NumberRange\Aggregate\NumberRangeChannel;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<NumberRangeChannelEntity>
 */
#[Package('member')]
class NumberRangeChannelCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'number_range_channel_collection';
    }

    protected function getExpectedClass(): string
    {
        return NumberRangeChannelEntity::class;
    }
}
