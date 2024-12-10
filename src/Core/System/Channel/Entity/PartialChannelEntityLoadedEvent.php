<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Entity;

use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\PartialEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;

#[Package('frontend')]
class PartialChannelEntityLoadedEvent extends ChannelEntityLoadedEvent
{
    /**
     * @var PartialEntity[]
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $entities;

    public function __construct(
        EntityDefinition $definition,
        array $entities,
        ChannelContext $context
    ) {
        parent::__construct($definition, $entities, $context);

        $this->name = $this->definition->getEntityName() . '.partial_loaded';
    }

    /**
     * @return PartialEntity[]
     */
    public function getEntities(): array
    {
        return $this->entities;
    }
}
