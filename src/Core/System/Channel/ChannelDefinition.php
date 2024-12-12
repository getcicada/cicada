<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel;

use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Content\Cms\CmsPageDefinition;
use Cicada\Core\Content\LandingPage\Aggregate\LandingPageChannel\LandingPageChannelDefinition;
use Cicada\Core\Content\LandingPage\LandingPageDefinition;
use Cicada\Core\Content\Seo\MainCategory\MainCategoryDefinition;
use Cicada\Core\Content\Seo\SeoUrl\SeoUrlDefinition;
use Cicada\Core\Content\Seo\SeoUrlTemplate\SeoUrlTemplateDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ListField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToManyIdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Language\LanguageDefinition;
use Cicada\Core\System\NumberRange\Aggregate\NumberRangeChannel\NumberRangeChannelDefinition;
use Cicada\Core\System\Channel\Aggregate\ChannelDomain\ChannelDomainDefinition;
use Cicada\Core\System\Channel\Aggregate\ChannelLanguage\ChannelLanguageDefinition;
use Cicada\Core\System\Channel\Aggregate\ChannelTranslation\ChannelTranslationDefinition;
use Cicada\Core\System\Channel\Aggregate\ChannelType\ChannelTypeDefinition;
use Cicada\Core\System\SystemConfig\SystemConfigDefinition;

#[Package('frontend')]
class ChannelDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'channel';
    final public const CALCULATION_TYPE_VERTICAL = 'vertical';
    final public const CALCULATION_TYPE_HORIZONTAL = 'horizontal';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return ChannelCollection::class;
    }

    public function getEntityClass(): string
    {
        return ChannelEntity::class;
    }

    public function getDefaults(): array
    {
        return [
            'homeEnabled' => true,
        ];
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),
            (new FkField('type_id', 'typeId', ChannelTypeDefinition::class))->addFlags(new Required()),
            (new FkField('language_id', 'languageId', LanguageDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new FkField('navigation_category_id', 'navigationCategoryId', CategoryDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new ReferenceVersionField(CategoryDefinition::class, 'navigation_category_version_id'))->addFlags(new ApiAware(), new Required()),
            (new IntField('navigation_category_depth', 'navigationCategoryDepth', 1))->addFlags(new ApiAware()),
            (new FkField('footer_category_id', 'footerCategoryId', CategoryDefinition::class))->addFlags(new ApiAware()),
            (new ReferenceVersionField(CategoryDefinition::class, 'footer_category_version_id'))->addFlags(new ApiAware(), new Required()),
            (new FkField('service_category_id', 'serviceCategoryId', CategoryDefinition::class))->addFlags(new ApiAware()),
            (new ReferenceVersionField(CategoryDefinition::class, 'service_category_version_id'))->addFlags(new ApiAware(), new Required()),
            (new FkField('hreflang_default_domain_id', 'hreflangDefaultDomainId', ChannelDomainDefinition::class))->addFlags(new ApiAware()),
            (new TranslatedField('name'))->addFlags(new ApiAware()),
            (new StringField('short_name', 'shortName'))->addFlags(new ApiAware()),
            (new StringField('tax_calculation_type', 'taxCalculationType'))->addFlags(new ApiAware()),
            (new StringField('access_key', 'accessKey'))->addFlags(new Required()),
            (new JsonField('configuration', 'configuration'))->addFlags(new ApiAware()),
            (new BoolField('active', 'active'))->addFlags(new ApiAware()),
            (new BoolField('hreflang_active', 'hreflangActive'))->addFlags(new ApiAware()),
            (new BoolField('maintenance', 'maintenance'))->addFlags(new ApiAware()),
            new ListField('maintenance_ip_whitelist', 'maintenanceIpWhitelist'),
            (new TranslatedField('customFields'))->addFlags(new ApiAware()),
            (new TranslationsAssociationField(ChannelTranslationDefinition::class, 'channel_id'))->addFlags(new Required()),
            new ManyToManyAssociationField('languages', LanguageDefinition::class, ChannelLanguageDefinition::class, 'channel_id', 'language_id'),
            new ManyToManyIdField('payment_method_ids', 'paymentMethodIds', 'paymentMethods'),
            new ManyToOneAssociationField('type', 'type_id', ChannelTypeDefinition::class, 'id', false),
            (new ManyToOneAssociationField('language', 'language_id', LanguageDefinition::class, 'id', false))->addFlags(new ApiAware()),
            new FkField('home_cms_page_id', 'homeCmsPageId', CmsPageDefinition::class),
            (new ReferenceVersionField(CmsPageDefinition::class, 'home_cms_page_version_id'))->addFlags(new Required()),
            new ManyToOneAssociationField('homeCmsPage', 'home_cms_page_id', CmsPageDefinition::class, 'id', false),
            new TranslatedField('homeSlotConfig'),
            new TranslatedField('homeEnabled'),
            new TranslatedField('homeName'),
            new TranslatedField('homeMetaTitle'),
            new TranslatedField('homeMetaDescription'),
            new TranslatedField('homeKeywords'),

            (new OneToManyAssociationField('domains', ChannelDomainDefinition::class, 'channel_id', 'id'))->addFlags(new ApiAware(), new CascadeDelete()),

            (new OneToManyAssociationField('systemConfigs', SystemConfigDefinition::class, 'channel_id'))->addFlags(new CascadeDelete()),
            (new ManyToOneAssociationField('navigationCategory', 'navigation_category_id', CategoryDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('footerCategory', 'footer_category_id', CategoryDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('serviceCategory', 'service_category_id', CategoryDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new OneToOneAssociationField('hreflangDefaultDomain', 'hreflang_default_domain_id', 'id', ChannelDomainDefinition::class, false))->addFlags(new ApiAware()),
            (new OneToManyAssociationField('numberRangeChannels', NumberRangeChannelDefinition::class, 'channel_id'))->addFlags(new CascadeDelete()),
            (new OneToManyAssociationField('seoUrls', SeoUrlDefinition::class, 'channel_id', 'id'))->addFlags(new CascadeDelete()),
            (new OneToManyAssociationField('seoUrlTemplates', SeoUrlTemplateDefinition::class, 'channel_id'))->addFlags(new CascadeDelete()),
            (new OneToManyAssociationField('mainCategories', MainCategoryDefinition::class, 'channel_id'))->addFlags(new CascadeDelete()),
            new ManyToManyAssociationField('landingPages', LandingPageDefinition::class, LandingPageChannelDefinition::class, 'channel_id', 'landing_page_id', 'id', 'id'),
        ]);
    }
}
