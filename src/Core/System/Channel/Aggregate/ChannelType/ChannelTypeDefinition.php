<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Aggregate\ChannelType;

use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ListField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\Aggregate\ChannelTypeTranslation\ChannelTypeTranslationDefinition;
use Cicada\Core\System\Channel\ChannelDefinition;

#[Package('frontend')]
class ChannelTypeDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'channel_type';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return ChannelTypeCollection::class;
    }

    public function getEntityClass(): string
    {
        return ChannelTypeEntity::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            new StringField('cover_url', 'coverUrl'),
            new StringField('icon_name', 'iconName'),
            new ListField('screenshot_urls', 'screenshotUrls', StringField::class),
            new TranslatedField('name'),
            new TranslatedField('manufacturer'),
            new TranslatedField('description'),
            new TranslatedField('descriptionLong'),
            new TranslatedField('customFields'),
            (new TranslationsAssociationField(ChannelTypeTranslationDefinition::class, 'channel_type_id'))->addFlags(new Required()),
            new OneToManyAssociationField('channels', ChannelDefinition::class, 'type_id', 'id'),
        ]);
    }
}
