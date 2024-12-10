<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme\Aggregate;

use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelDefinition;
use Cicada\Frontend\Theme\ThemeDefinition;

#[Package('frontend')]
class ThemeChannelDefinition extends MappingEntityDefinition
{
    final public const ENTITY_NAME = 'theme_channel';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new FkField('channel_id', 'channelId', ChannelDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('theme_id', 'themeId', ThemeDefinition::class))->addFlags(new Required()),
            new ManyToOneAssociationField('theme', 'theme_id', ThemeDefinition::class),
            new ManyToOneAssociationField('channel', 'channel_id', ChannelDefinition::class),
        ]);
    }
}
