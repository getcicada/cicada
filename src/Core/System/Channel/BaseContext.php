<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel;

use Cicada\Frontend\Member\Aggregate\MemberGroup\MemberGroupEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
/**
 * @internal Use ChannelContext for extensions
 */
#[Package('core')]
class BaseContext
{
    public function __construct(
        protected Context $context,
        protected ChannelEntity $channel,
        protected MemberGroupEntity $currentMemberGroup,
    ) {
    }

    public function getCurrentMemberGroup(): MemberGroupEntity
    {
        return $this->currentMemberGroup;
    }

    public function getChannel(): ChannelEntity
    {
        return $this->channel;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
    public function getApiAlias(): string
    {
        return 'base_channel_context';
    }
}
