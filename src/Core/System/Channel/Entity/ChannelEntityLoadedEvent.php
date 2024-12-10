<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Entity;

use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Cicada\Core\Framework\Event\CicadaChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;

#[Package('frontend')]
class ChannelEntityLoadedEvent extends EntityLoadedEvent implements CicadaChannelEvent
{
    private readonly ChannelContext $channelContext;

    /**
     * @param Entity[] $entities
     */
    public function __construct(
        EntityDefinition $definition,
        array $entities,
        ChannelContext $context
    ) {
        parent::__construct($definition, $entities, $context->getContext());
        $this->channelContext = $context;
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
