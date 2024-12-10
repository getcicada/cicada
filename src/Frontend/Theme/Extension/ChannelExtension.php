<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme\Extension;

use Cicada\Core\Framework\DataAbstractionLayer\EntityExtension;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelDefinition;
use Cicada\Frontend\Theme\Aggregate\ThemeChannelDefinition;
use Cicada\Frontend\Theme\ThemeDefinition;

#[Package('frontend')]
class ChannelExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new ManyToManyAssociationField('themes', ThemeDefinition::class, ThemeChannelDefinition::class, 'channel_id', 'theme_id')
        );
    }

    public function getDefinitionClass(): string
    {
        return ChannelDefinition::class;
    }

    public function getEntityName(): string
    {
        return ChannelDefinition::ENTITY_NAME;
    }
}
