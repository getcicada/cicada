<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Entity;

use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityIdSearchResultLoadedEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Cicada\Core\Framework\Event\CicadaChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;

#[Package('frontend')]
class ChannelEntityIdSearchResultLoadedEvent extends EntityIdSearchResultLoadedEvent implements CicadaChannelEvent
{
    public function __construct(
        EntityDefinition $definition,
        IdSearchResult $result,
        private readonly ChannelContext $channelContext
    ) {
        parent::__construct($definition, $result);
    }

    public function getName(): string
    {
        return 'channel.' . parent::getName();
    }

    public function getChannelContext(): ChannelContext
    {
        return $this->channelContext;
    }
}
