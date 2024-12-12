<?php declare(strict_types=1);

namespace Cicada\Core\System\Language;

use Cicada\Core\Content\Category\Aggregate\CategoryTranslation\CategoryTranslationDefinition;
use Cicada\Core\Content\Cms\Aggregate\CmsPageTranslation\CmsPageTranslationDefinition;
use Cicada\Core\Content\Cms\Aggregate\CmsSlotTranslation\CmsSlotTranslationDefinition;
use Cicada\Core\Content\LandingPage\Aggregate\LandingPageTranslation\LandingPageTranslationDefinition;
use Cicada\Core\Content\Media\Aggregate\MediaTranslation\MediaTranslationDefinition;
use Cicada\Core\Content\Seo\SeoUrl\SeoUrlDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ChildrenAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\RestrictDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ParentAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ParentFkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Aggregate\PluginTranslation\PluginTranslationDefinition;
use Cicada\Core\System\Locale\Aggregate\LocaleTranslation\LocaleTranslationDefinition;
use Cicada\Core\System\Locale\LocaleDefinition;
use Cicada\Core\System\NumberRange\Aggregate\NumberRangeTranslation\NumberRangeTranslationDefinition;
use Cicada\Core\System\NumberRange\Aggregate\NumberRangeTypeTranslation\NumberRangeTypeTranslationDefinition;
use Cicada\Core\System\Channel\Aggregate\ChannelDomain\ChannelDomainDefinition;
use Cicada\Core\System\Channel\Aggregate\ChannelLanguage\ChannelLanguageDefinition;
use Cicada\Core\System\Channel\Aggregate\ChannelTranslation\ChannelTranslationDefinition;
use Cicada\Core\System\Channel\Aggregate\ChannelTypeTranslation\ChannelTypeTranslationDefinition;
use Cicada\Core\System\Channel\ChannelDefinition;

#[Package('frontend')]
class LanguageDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'language';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return LanguageCollection::class;
    }

    public function getEntityClass(): string
    {
        return LanguageEntity::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        $collection = new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),
            (new ParentFkField(self::class))->addFlags(new ApiAware()),
            (new FkField('locale_id', 'localeId', LocaleDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new FkField('translation_code_id', 'translationCodeId', LocaleDefinition::class))->addFlags(new ApiAware()),

            (new StringField('name', 'name'))->addFlags(new ApiAware(), new Required()),
            (new CustomFields())->addFlags(new ApiAware()),
            (new ParentAssociationField(self::class, 'id'))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('locale', 'locale_id', LocaleDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('translationCode', 'translation_code_id', LocaleDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new ChildrenAssociationField(self::class))->addFlags(new ApiAware()),
            new ManyToManyAssociationField('channels', ChannelDefinition::class, ChannelLanguageDefinition::class, 'language_id', 'channel_id'),

            new OneToManyAssociationField('channelDefaultAssignments', ChannelDefinition::class, 'language_id', 'id'),
            (new OneToManyAssociationField('channelDomains', ChannelDomainDefinition::class, 'language_id'))->addFlags(new RestrictDelete()),

            // Translation Associations, not available over sales-channel-api
            (new OneToManyAssociationField('categoryTranslations', CategoryTranslationDefinition::class, 'language_id'))->addFlags(new CascadeDelete()),
            (new OneToManyAssociationField('localeTranslations', LocaleTranslationDefinition::class, 'language_id'))->addFlags(new CascadeDelete()),
            (new OneToManyAssociationField('mediaTranslations', MediaTranslationDefinition::class, 'language_id'))->addFlags(new CascadeDelete()),
            (new OneToManyAssociationField('channelTranslations', ChannelTranslationDefinition::class, 'language_id'))->addFlags(new CascadeDelete()),
            (new OneToManyAssociationField('channelTypeTranslations', ChannelTypeTranslationDefinition::class, 'language_id'))->addFlags(new CascadeDelete()),
            (new OneToManyAssociationField('pluginTranslations', PluginTranslationDefinition::class, 'language_id'))->addFlags(new CascadeDelete()),
            (new OneToManyAssociationField('cmsPageTranslations', CmsPageTranslationDefinition::class, 'language_id'))->addFlags(new CascadeDelete()),
            (new OneToManyAssociationField('cmsSlotTranslations', CmsSlotTranslationDefinition::class, 'language_id'))->addFlags(new CascadeDelete()),
            (new OneToManyAssociationField('numberRangeTypeTranslations', NumberRangeTypeTranslationDefinition::class, 'language_id'))->addFlags(new CascadeDelete()),
            (new OneToManyAssociationField('numberRangeTranslations', NumberRangeTranslationDefinition::class, 'language_id'))->addFlags(new CascadeDelete()),
            (new OneToManyAssociationField('seoUrlTranslations', SeoUrlDefinition::class, 'language_id'))->addFlags(new CascadeDelete()),
            (new OneToManyAssociationField('landingPageTranslations', LandingPageTranslationDefinition::class, 'language_id'))->addFlags(new CascadeDelete()),
        ]);

        return $collection;
    }
}
