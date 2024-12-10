<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Channel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\DataBag;
use Cicada\Core\System\Channel\ChannelContext;

#[Package('core')]
class ChannelContextSwitcher
{
    /**
     * @internal
     */
    public function __construct(private readonly AbstractContextSwitchRoute $contextSwitchRoute)
    {
    }

    public function update(DataBag $data, ChannelContext $context): void
    {
        $this->contextSwitchRoute->switchContext($data->toRequestDataBag(), $context);
    }
}
