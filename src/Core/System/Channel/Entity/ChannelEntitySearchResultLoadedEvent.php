<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Entity;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntitySearchResultLoadedEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Event\CicadaChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;

/**
 * @template TEntityCollection of EntityCollection
 *
 * @extends EntitySearchResultLoadedEvent<TEntityCollection>
 */
#[Package('frontend')]
class ChannelEntitySearchResultLoadedEvent extends EntitySearchResultLoadedEvent implements CicadaChannelEvent
{
    /**
     * @param EntitySearchResult<TEntityCollection> $result
     */
    public function __construct(
        EntityDefinition $definition,
        EntitySearchResult $result,
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
