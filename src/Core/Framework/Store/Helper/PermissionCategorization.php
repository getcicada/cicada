<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Helper;

use Cicada\Frontend\Member\Aggregate\MemberAddress\MemberAddressDefinition;
use Cicada\Frontend\Member\Aggregate\MemberGroup\MemberGroupDefinition;
use Cicada\Frontend\Member\Aggregate\MemberGroupRegistrationChannel\MemberGroupRegistrationChannelDefinition;
use Cicada\Frontend\Member\Aggregate\MemberGroupTranslation\MemberGroupTranslationDefinition;
use Cicada\Frontend\Member\Aggregate\MemberRecovery\MemberRecoveryDefinition;
use Cicada\Frontend\Member\Aggregate\MemberTag\MemberTagDefinition;
use Cicada\Frontend\Member\MemberDefinition;
use Cicada\Core\Checkout\Document\Aggregate\DocumentBaseConfig\DocumentBaseConfigDefinition;
use Cicada\Core\Checkout\Document\Aggregate\DocumentBaseConfigChannel\DocumentBaseConfigChannelDefinition;
use Cicada\Core\Checkout\Document\Aggregate\DocumentType\DocumentTypeDefinition;
use Cicada\Core\Checkout\Document\Aggregate\DocumentTypeTranslation\DocumentTypeTranslationDefinition;
use Cicada\Core\Checkout\Document\DocumentDefinition;
use Cicada\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressDefinition;
use Cicada\Core\Checkout\Order\Aggregate\OrderMember\OrderMemberDefinition;
use Cicada\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Cicada\Core\Checkout\Order\Aggregate\OrderDeliveryPosition\OrderDeliveryPositionDefinition;
use Cicada\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemDefinition;
use Cicada\Core\Checkout\Order\Aggregate\OrderTag\OrderTagDefinition;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Cicada\Core\Checkout\Order\OrderDefinition;
use Cicada\Core\Checkout\Payment\Aggregate\PaymentMethodTranslation\PaymentMethodTranslationDefinition;
use Cicada\Core\Checkout\Payment\PaymentMethodDefinition;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionCartRule\PromotionCartRuleDefinition;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionDiscount\PromotionDiscountDefinition;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionDiscountPrice\PromotionDiscountPriceDefinition;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionDiscountRule\PromotionDiscountRuleDefinition;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionIndividualCode\PromotionIndividualCodeDefinition;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionOrderRule\PromotionOrderRuleDefinition;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionPersonaMember\PromotionPersonaMemberDefinition;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionPersonaRule\PromotionPersonaRuleDefinition;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionChannel\PromotionChannelDefinition;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionSetGroup\PromotionSetGroupDefinition;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionSetGroupRule\PromotionSetGroupRuleDefinition;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionTranslation\PromotionTranslationDefinition;
use Cicada\Core\Checkout\Promotion\PromotionDefinition;
use Cicada\Core\Content\Category\Aggregate\CategoryTag\CategoryTagDefinition;
use Cicada\Core\Content\Category\Aggregate\CategoryTranslation\CategoryTranslationDefinition;
use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockDefinition;
use Cicada\Core\Content\Cms\Aggregate\CmsPageTranslation\CmsPageTranslationDefinition;
use Cicada\Core\Content\Cms\Aggregate\CmsSection\CmsSectionDefinition;
use Cicada\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotDefinition;
use Cicada\Core\Content\Cms\Aggregate\CmsSlotTranslation\CmsSlotTranslationDefinition;
use Cicada\Core\Content\Cms\CmsPageDefinition;
use Cicada\Core\Content\ImportExport\Aggregate\ImportExportFile\ImportExportFileDefinition;
use Cicada\Core\Content\ImportExport\Aggregate\ImportExportLog\ImportExportLogDefinition;
use Cicada\Core\Content\ImportExport\ImportExportProfileDefinition;
use Cicada\Core\Content\ImportExport\ImportExportProfileTranslationDefinition;
use Cicada\Core\Content\MailTemplate\Aggregate\MailHeaderFooter\MailHeaderFooterDefinition;
use Cicada\Core\Content\MailTemplate\Aggregate\MailHeaderFooterTranslation\MailHeaderFooterTranslationDefinition;
use Cicada\Core\Content\MailTemplate\Aggregate\MailTemplateMedia\MailTemplateMediaDefinition;
use Cicada\Core\Content\MailTemplate\Aggregate\MailTemplateTranslation\MailTemplateTranslationDefinition;
use Cicada\Core\Content\MailTemplate\Aggregate\MailTemplateType\MailTemplateTypeDefinition;
use Cicada\Core\Content\MailTemplate\Aggregate\MailTemplateTypeTranslation\MailTemplateTypeTranslationDefinition;
use Cicada\Core\Content\MailTemplate\MailTemplateDefinition;
use Cicada\Core\Content\Media\Aggregate\MediaDefaultFolder\MediaDefaultFolderDefinition;
use Cicada\Core\Content\Media\Aggregate\MediaFolder\MediaFolderDefinition;
use Cicada\Core\Content\Media\Aggregate\MediaFolderConfiguration\MediaFolderConfigurationDefinition;
use Cicada\Core\Content\Media\Aggregate\MediaFolderConfigurationMediaThumbnailSize\MediaFolderConfigurationMediaThumbnailSizeDefinition;
use Cicada\Core\Content\Media\Aggregate\MediaTag\MediaTagDefinition;
use Cicada\Core\Content\Media\Aggregate\MediaThumbnail\MediaThumbnailDefinition;
use Cicada\Core\Content\Media\Aggregate\MediaThumbnailSize\MediaThumbnailSizeDefinition;
use Cicada\Core\Content\Media\Aggregate\MediaTranslation\MediaTranslationDefinition;
use Cicada\Core\Content\Media\MediaDefinition;
use Cicada\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientDefinition;
use Cicada\Core\Content\Newsletter\Aggregate\NewsletterRecipientTag\NewsletterRecipientTagDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductCategory\ProductCategoryDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductCategoryTree\ProductCategoryTreeDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductConfiguratorSetting\ProductConfiguratorSettingDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductCrossSelling\ProductCrossSellingDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductCrossSellingAssignedProducts\ProductCrossSellingAssignedProductsDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductCrossSellingTranslation\ProductCrossSellingTranslationDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductCustomFieldSet\ProductCustomFieldSetDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductFeatureSet\ProductFeatureSetDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductFeatureSetTranslation\ProductFeatureSetTranslationDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductKeywordDictionary\ProductKeywordDictionaryDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductManufacturerTranslation\ProductManufacturerTranslationDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductMedia\ProductMediaDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductOption\ProductOptionDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductPrice\ProductPriceDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductProperty\ProductPropertyDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductReview\ProductReviewDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductSearchKeyword\ProductSearchKeywordDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductTag\ProductTagDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Content\Product\Channel\Sorting\ProductSortingDefinition;
use Cicada\Core\Content\ProductExport\ProductExportDefinition;
use Cicada\Core\Content\ProductStream\Aggregate\ProductStreamFilter\ProductStreamFilterDefinition;
use Cicada\Core\Content\ProductStream\Aggregate\ProductStreamTranslation\ProductStreamTranslationDefinition;
use Cicada\Core\Content\ProductStream\ProductStreamDefinition;
use Cicada\Core\Content\Rule\Aggregate\RuleCondition\RuleConditionDefinition;
use Cicada\Core\Content\Rule\RuleDefinition;
use Cicada\Core\Content\Seo\SeoUrl\SeoUrlDefinition;
use Cicada\Core\Content\Seo\SeoUrlTemplate\SeoUrlTemplateDefinition;
use Cicada\Core\Framework\App\Aggregate\ActionButton\ActionButtonDefinition;
use Cicada\Core\Framework\App\Aggregate\ActionButtonTranslation\ActionButtonTranslationDefinition;
use Cicada\Core\Framework\App\Aggregate\AppTranslation\AppTranslationDefinition;
use Cicada\Core\Framework\App\AppDefinition;
use Cicada\Core\Framework\App\Template\TemplateDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Version\VersionDefinition;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Country\Aggregate\CountryState\CountryStateDefinition;
use Cicada\Core\System\Country\CountryDefinition;
use Cicada\Core\System\Currency\CurrencyDefinition;
use Cicada\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetDefinition;
use Cicada\Core\System\CustomField\Aggregate\CustomFieldSetRelation\CustomFieldSetRelationDefinition;
use Cicada\Core\System\CustomField\CustomFieldDefinition;
use Cicada\Core\System\DeliveryTime\DeliveryTimeDefinition;
use Cicada\Core\System\Integration\IntegrationDefinition;
use Cicada\Core\System\Language\LanguageDefinition;
use Cicada\Core\System\Locale\Aggregate\LocaleTranslation\LocaleTranslationDefinition;
use Cicada\Core\System\Locale\LocaleDefinition;
use Cicada\Core\System\NumberRange\Aggregate\NumberRangeChannel\NumberRangeChannelDefinition;
use Cicada\Core\System\NumberRange\Aggregate\NumberRangeState\NumberRangeStateDefinition;
use Cicada\Core\System\NumberRange\Aggregate\NumberRangeType\NumberRangeTypeDefinition;
use Cicada\Core\System\NumberRange\NumberRangeDefinition;
use Cicada\Core\System\Channel\Aggregate\ChannelAnalytics\ChannelAnalyticsDefinition;
use Cicada\Core\System\Channel\Aggregate\ChannelCountry\ChannelCountryDefinition;
use Cicada\Core\System\Channel\Aggregate\ChannelCurrency\ChannelCurrencyDefinition;
use Cicada\Core\System\Channel\Aggregate\ChannelDomain\ChannelDomainDefinition;
use Cicada\Core\System\Channel\Aggregate\ChannelLanguage\ChannelLanguageDefinition;
use Cicada\Core\System\Channel\Aggregate\ChannelPaymentMethod\ChannelPaymentMethodDefinition;
use Cicada\Core\System\Channel\Aggregate\ChannelShippingMethod\ChannelShippingMethodDefinition;
use Cicada\Core\System\Channel\Aggregate\ChannelTranslation\ChannelTranslationDefinition;
use Cicada\Core\System\Channel\Aggregate\ChannelType\ChannelTypeDefinition;
use Cicada\Core\System\Channel\Aggregate\ChannelTypeTranslation\ChannelTypeTranslationDefinition;
use Cicada\Core\System\Channel\ChannelDefinition;
use Cicada\Core\System\Salutation\Aggregate\SalutationTranslation\SalutationTranslationDefinition;
use Cicada\Core\System\Salutation\SalutationDefinition;
use Cicada\Core\System\StateMachine\Aggregation\StateMachineHistory\StateMachineHistoryDefinition;
use Cicada\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateDefinition;
use Cicada\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateTranslationDefinition;
use Cicada\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionDefinition;
use Cicada\Core\System\StateMachine\StateMachineDefinition;
use Cicada\Core\System\StateMachine\StateMachineTranslationDefinition;
use Cicada\Core\System\SystemConfig\SystemConfigDefinition;
use Cicada\Core\System\Tag\TagDefinition;
use Cicada\Core\System\Tax\Aggregate\TaxRule\TaxRuleDefinition;
use Cicada\Core\System\Tax\Aggregate\TaxRuleType\TaxRuleTypeDefinition;
use Cicada\Core\System\Tax\TaxDefinition;
use Cicada\Core\System\Unit\UnitDefinition;
use Cicada\Core\System\User\Aggregate\UserAccessKey\UserAccessKeyDefinition;
use Cicada\Core\System\User\Aggregate\UserRecovery\UserRecoveryDefinition;
use Cicada\Core\System\User\UserDefinition;

/**
 * @internal
 */
#[Package('member')]
class PermissionCategorization
{
    private const CATEGORY_APP = 'app';
    private const CATEGORY_ADMIN_USER = 'admin_user';
    private const CATEGORY_CATEGORY = 'category';
    private const CATEGORY_CMS = 'cms';
    private const CATEGORY_Member = 'member';
    private const CATEGORY_CUSTOM_FIELDS = 'custom_fields';
    private const CATEGORY_DOCUMENTS = 'documents';
    private const CATEGORY_GOOGLE_SHOPPING = 'google_shopping';
    private const CATEGORY_IMPORT_EXPORT = 'import_export';
    private const CATEGORY_MAIL_TEMPLATES = 'mail_templates';
    private const CATEGORY_MEDIA = 'media';
    private const CATEGORY_NEWSLETTER = 'newsletter';
    private const CATEGORY_ORDER = 'order';
    private const CATEGORY_OTHER = 'other';
    private const CATEGORY_PAYMENT = 'payment';
    private const CATEGORY_PRODUCT = 'product';
    private const CATEGORY_PROMOTION = 'promotion';
    private const CATEGORY_RULES = 'rules';
    private const CATEGORY_SALES_CHANNEL = 'channel';
    private const CATEGORY_SETTINGS = 'settings';
    private const CATEGORY_SOCIAL_SHOPPING = 'social_shopping';
    private const CATEGORY_TAG = 'tag';
    private const CATEGORY_THEME = 'theme';
    private const CATEGORY_ADDITIONAL_PRIVILEGES = 'additional_privileges';

    /**
     * @see \Cicada\Frontend\Theme\ThemeDefinition::ENTITY_NAME
     */
    private const THEME_ENTITY_NAME = 'theme';
    /**
     * @see \Cicada\Frontend\Theme\Aggregate\ThemeTranslationDefinition::ENTITY_NAME
     */
    private const THEME_TRANSLATION_ENTITY_NAME = 'theme_translation';
    /**
     * @see \Cicada\Frontend\Theme\Aggregate\ThemeMediaDefinition::ENTITY_NAME
     */
    private const THEME_MEDIA_ENTITY_NAME = 'theme_media';
    /**
     * @see \Cicada\Frontend\Theme\Aggregate\ThemeChannelDefinition::ENTITY_NAME
     */
    private const THEME_SALES_CHANNEL_ENTITY_NAME = 'theme_channel';

    private const PERMISSION_CATEGORIES = [
        self::CATEGORY_ADMIN_USER => [
            IntegrationDefinition::ENTITY_NAME,
            UserDefinition::ENTITY_NAME,
            UserAccessKeyDefinition::ENTITY_NAME,
            UserRecoveryDefinition::ENTITY_NAME,
        ],
        self::CATEGORY_APP => [
            TemplateDefinition::ENTITY_NAME,
            AppDefinition::ENTITY_NAME,
            AppTranslationDefinition::ENTITY_NAME,
            ActionButtonDefinition::ENTITY_NAME,
            ActionButtonTranslationDefinition::ENTITY_NAME,
        ],
        self::CATEGORY_CATEGORY => [
            CategoryDefinition::ENTITY_NAME,
            CategoryTranslationDefinition::ENTITY_NAME,
            CategoryTagDefinition::ENTITY_NAME,
        ],
        self::CATEGORY_CMS => [
            CmsBlockDefinition::ENTITY_NAME,
            CmsPageDefinition::ENTITY_NAME,
            CmsPageTranslationDefinition::ENTITY_NAME,
            CmsSectionDefinition::ENTITY_NAME,
            CmsSlotDefinition::ENTITY_NAME,
            CmsSlotTranslationDefinition::ENTITY_NAME,
        ],
        self::CATEGORY_Member => [
            MemberDefinition::ENTITY_NAME,
            MemberAddressDefinition::ENTITY_NAME,
            MemberGroupDefinition::ENTITY_NAME,
            MemberGroupTranslationDefinition::ENTITY_NAME,
            MemberGroupRegistrationChannelDefinition::ENTITY_NAME,
            MemberRecoveryDefinition::ENTITY_NAME,
            MemberTagDefinition::ENTITY_NAME,
        ],
        self::CATEGORY_CUSTOM_FIELDS => [
            CustomFieldDefinition::ENTITY_NAME,
            CustomFieldSetDefinition::ENTITY_NAME,
            CustomFieldSetRelationDefinition::ENTITY_NAME,
        ],
        self::CATEGORY_DOCUMENTS => [
            DocumentDefinition::ENTITY_NAME,
            DocumentBaseConfigDefinition::ENTITY_NAME,
            DocumentBaseConfigChannelDefinition::ENTITY_NAME,
            DocumentTypeDefinition::ENTITY_NAME,
            DocumentTypeTranslationDefinition::ENTITY_NAME,
        ],
        self::CATEGORY_GOOGLE_SHOPPING => [
            'swag_google_shopping_account',
            'swag_google_shopping_ads_account',
            'swag_google_shopping_list_ads_account',
            'swag_google_shopping_category',
            'swag_google_shopping_merchant_account',
        ],
        self::CATEGORY_IMPORT_EXPORT => [
            ImportExportFileDefinition::ENTITY_NAME,
            ImportExportLogDefinition::ENTITY_NAME,
            ImportExportProfileDefinition::ENTITY_NAME,
            ImportExportProfileTranslationDefinition::ENTITY_NAME,
        ],
        self::CATEGORY_MAIL_TEMPLATES => [
            MailHeaderFooterDefinition::ENTITY_NAME,
            MailHeaderFooterTranslationDefinition::ENTITY_NAME,
            MailTemplateDefinition::ENTITY_NAME,
            MailTemplateTranslationDefinition::ENTITY_NAME,
            MailTemplateMediaDefinition::ENTITY_NAME,
            MailTemplateTypeDefinition::ENTITY_NAME,
            MailTemplateTypeTranslationDefinition::ENTITY_NAME,
        ],
        self::CATEGORY_MEDIA => [
            MediaDefinition::ENTITY_NAME,
            MediaTranslationDefinition::ENTITY_NAME,
            MediaDefaultFolderDefinition::ENTITY_NAME,
            MediaFolderDefinition::ENTITY_NAME,
            MediaFolderConfigurationDefinition::ENTITY_NAME,
            MediaFolderConfigurationMediaThumbnailSizeDefinition::ENTITY_NAME,
            MediaTagDefinition::ENTITY_NAME,
            MediaThumbnailDefinition::ENTITY_NAME,
            MediaThumbnailSizeDefinition::ENTITY_NAME,
        ],
        self::CATEGORY_NEWSLETTER => [
            NewsletterRecipientDefinition::ENTITY_NAME,
            NewsletterRecipientTagDefinition::ENTITY_NAME,
        ],
        self::CATEGORY_ORDER => [
            OrderDefinition::ENTITY_NAME,
            OrderAddressDefinition::ENTITY_NAME,
            OrderMemberDefinition::ENTITY_NAME,
            OrderDeliveryDefinition::ENTITY_NAME,
            OrderDeliveryPositionDefinition::ENTITY_NAME,
            OrderLineItemDefinition::ENTITY_NAME,
            OrderTagDefinition::ENTITY_NAME,
            OrderTransactionDefinition::ENTITY_NAME,
        ],
        self::CATEGORY_PAYMENT => [
            PaymentMethodDefinition::ENTITY_NAME,
            PaymentMethodTranslationDefinition::ENTITY_NAME,
        ],
        self::CATEGORY_PRODUCT => [
            ProductDefinition::ENTITY_NAME,
            ProductCategoryDefinition::ENTITY_NAME,
            ProductCategoryTreeDefinition::ENTITY_NAME,
            ProductConfiguratorSettingDefinition::ENTITY_NAME,
            ProductCrossSellingDefinition::ENTITY_NAME,
            ProductCrossSellingAssignedProductsDefinition::ENTITY_NAME,
            ProductCrossSellingTranslationDefinition::ENTITY_NAME,
            ProductExportDefinition::ENTITY_NAME,
            ProductKeywordDictionaryDefinition::ENTITY_NAME,
            ProductManufacturerDefinition::ENTITY_NAME,
            ProductManufacturerTranslationDefinition::ENTITY_NAME,
            ProductMediaDefinition::ENTITY_NAME,
            ProductOptionDefinition::ENTITY_NAME,
            ProductPriceDefinition::ENTITY_NAME,
            ProductPropertyDefinition::ENTITY_NAME,
            ProductReviewDefinition::ENTITY_NAME,
            ProductSearchKeywordDefinition::ENTITY_NAME,
            ProductStreamDefinition::ENTITY_NAME,
            ProductStreamFilterDefinition::ENTITY_NAME,
            ProductStreamTranslationDefinition::ENTITY_NAME,
            ProductTagDefinition::ENTITY_NAME,
            ProductVisibilityDefinition::ENTITY_NAME,
            ProductSortingDefinition::ENTITY_NAME,
            ProductTranslationDefinition::ENTITY_NAME,
            ProductFeatureSetDefinition::ENTITY_NAME,
            ProductFeatureSetTranslationDefinition::ENTITY_NAME,
            ProductCustomFieldSetDefinition::ENTITY_NAME,
        ],
        self::CATEGORY_PROMOTION => [
            PromotionDefinition::ENTITY_NAME,
            PromotionTranslationDefinition::ENTITY_NAME,
            PromotionCartRuleDefinition::ENTITY_NAME,
            PromotionDiscountDefinition::ENTITY_NAME,
            PromotionDiscountPriceDefinition::ENTITY_NAME,
            PromotionDiscountRuleDefinition::ENTITY_NAME,
            PromotionIndividualCodeDefinition::ENTITY_NAME,
            PromotionOrderRuleDefinition::ENTITY_NAME,
            PromotionPersonaMemberDefinition::ENTITY_NAME,
            PromotionPersonaRuleDefinition::ENTITY_NAME,
            PromotionChannelDefinition::ENTITY_NAME,
            PromotionSetGroupDefinition::ENTITY_NAME,
            PromotionSetGroupRuleDefinition::ENTITY_NAME,
        ],
        self::CATEGORY_RULES => [
            RuleDefinition::ENTITY_NAME,
            RuleConditionDefinition::ENTITY_NAME,
        ],
        self::CATEGORY_SALES_CHANNEL => [
            ChannelDefinition::ENTITY_NAME,
            ChannelAnalyticsDefinition::ENTITY_NAME,
            ChannelCountryDefinition::ENTITY_NAME,
            ChannelCurrencyDefinition::ENTITY_NAME,
            ChannelDomainDefinition::ENTITY_NAME,
            ChannelLanguageDefinition::ENTITY_NAME,
            ChannelPaymentMethodDefinition::ENTITY_NAME,
            ChannelShippingMethodDefinition::ENTITY_NAME,
            ChannelTranslationDefinition::ENTITY_NAME,
            ChannelTypeDefinition::ENTITY_NAME,
            ChannelTypeTranslationDefinition::ENTITY_NAME,
        ],
        self::CATEGORY_SETTINGS => [
            CountryDefinition::ENTITY_NAME,
            CountryStateDefinition::ENTITY_NAME,
            CurrencyDefinition::ENTITY_NAME,
            DeliveryTimeDefinition::ENTITY_NAME,
            LanguageDefinition::ENTITY_NAME,
            LocaleDefinition::ENTITY_NAME,
            LocaleTranslationDefinition::ENTITY_NAME,
            NumberRangeDefinition::ENTITY_NAME,
            NumberRangeChannelDefinition::ENTITY_NAME,
            NumberRangeStateDefinition::ENTITY_NAME,
            NumberRangeTypeDefinition::ENTITY_NAME,
            SalutationDefinition::ENTITY_NAME,
            SalutationTranslationDefinition::ENTITY_NAME,
            SeoUrlDefinition::ENTITY_NAME,
            SeoUrlTemplateDefinition::ENTITY_NAME,
            StateMachineDefinition::ENTITY_NAME,
            StateMachineHistoryDefinition::ENTITY_NAME,
            StateMachineStateDefinition::ENTITY_NAME,
            StateMachineStateTranslationDefinition::ENTITY_NAME,
            StateMachineTransitionDefinition::ENTITY_NAME,
            StateMachineTranslationDefinition::ENTITY_NAME,
            SystemConfigDefinition::ENTITY_NAME,
            TaxDefinition::ENTITY_NAME,
            TaxRuleDefinition::ENTITY_NAME,
            TaxRuleTypeDefinition::ENTITY_NAME,
            UnitDefinition::ENTITY_NAME,
            VersionDefinition::ENTITY_NAME,
        ],
        self::CATEGORY_SOCIAL_SHOPPING => [
            'swag_social_shopping_channel',
            'swag_social_shopping_product_error',
        ],
        self::CATEGORY_TAG => [
            TagDefinition::ENTITY_NAME,
        ],
        self::CATEGORY_THEME => [
            self::THEME_ENTITY_NAME,
            self::THEME_TRANSLATION_ENTITY_NAME,
            self::THEME_MEDIA_ENTITY_NAME,
            self::THEME_SALES_CHANNEL_ENTITY_NAME,
        ],
        self::CATEGORY_ADDITIONAL_PRIVILEGES => [
            'additional_privileges',
        ],
    ];

    public static function isInCategory(string $entity, string $category): bool
    {
        if ($category === self::CATEGORY_OTHER) {
            $allCategories = array_merge(...array_values(self::PERMISSION_CATEGORIES));

            return !\in_array($entity, $allCategories, true);
        }

        return \in_array($entity, self::PERMISSION_CATEGORIES[$category], true);
    }

    /**
     * @return string[]
     */
    public static function getCategoryNames(): array
    {
        $categories = array_keys(self::PERMISSION_CATEGORIES);
        $categories[] = self::CATEGORY_OTHER;

        return $categories;
    }
}
