<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Aggregate\ChannelTypeTranslation;

use Cicada\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\Aggregate\ChannelType\ChannelTypeDefinition;

#[Package('frontend')]
class ChannelTypeTranslationDefinition extends EntityTranslationDefinition
{
    final public const ENTITY_NAME = 'channel_type_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return ChannelTypeTranslationCollection::class;
    }

    public function getEntityClass(): string
    {
        return ChannelTypeTranslationEntity::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function getParentDefinitionClass(): string
    {
        return ChannelTypeDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('name', 'name'))->addFlags(new Required()),
            new StringField('manufacturer', 'manufacturer'),
            new StringField('description', 'description'),
            (new LongTextField('description_long', 'descriptionLong'))->addFlags(new ApiAware(), new AllowHtml()),
            new CustomFields(),
        ]);
    }
}
