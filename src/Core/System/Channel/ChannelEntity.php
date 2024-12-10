<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel;

use Cicada\Frontend\Member\Aggregate\MemberGroup\MemberGroupCollection;
use Cicada\Frontend\Member\Aggregate\MemberGroup\MemberGroupEntity;
use Cicada\Frontend\Member\Aggregate\MemberWishlist\MemberWishlistCollection;
use Cicada\Frontend\Member\MemberCollection;
use Cicada\Core\Checkout\Document\Aggregate\DocumentBaseConfig\DocumentBaseConfigDefinition;
use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionChannel\PromotionChannelCollection;
use Cicada\Core\Checkout\Shipping\ShippingMethodCollection;
use Cicada\Core\Checkout\Shipping\ShippingMethodEntity;
use Cicada\Core\Content\Category\CategoryEntity;
use Cicada\Core\Content\Cms\CmsPageEntity;
use Cicada\Core\Content\LandingPage\LandingPageCollection;
use Cicada\Core\Content\MailTemplate\Aggregate\MailHeaderFooter\MailHeaderFooterEntity;
use Cicada\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientCollection;
use Cicada\Core\Content\Product\Aggregate\ProductReview\ProductReviewCollection;
use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityCollection;
use Cicada\Core\Content\ProductExport\ProductExportCollection;
use Cicada\Core\Content\Seo\MainCategory\MainCategoryCollection;
use Cicada\Core\Content\Seo\SeoUrl\SeoUrlCollection;
use Cicada\Core\Content\Seo\SeoUrlTemplate\SeoUrlTemplateCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Cicada\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Country\CountryCollection;
use Cicada\Core\System\Country\CountryEntity;
use Cicada\Core\System\Currency\CurrencyCollection;
use Cicada\Core\System\Currency\CurrencyEntity;
use Cicada\Core\System\Language\LanguageCollection;
use Cicada\Core\System\Language\LanguageEntity;
use Cicada\Core\System\NumberRange\Aggregate\NumberRangeChannel\NumberRangeChannelCollection;
use Cicada\Core\System\Channel\Aggregate\ChannelAnalytics\ChannelAnalyticsEntity;
use Cicada\Core\System\Channel\Aggregate\ChannelDomain\ChannelDomainCollection;
use Cicada\Core\System\Channel\Aggregate\ChannelDomain\ChannelDomainEntity;
use Cicada\Core\System\Channel\Aggregate\ChannelTranslation\ChannelTranslationCollection;
use Cicada\Core\System\Channel\Aggregate\ChannelType\ChannelTypeEntity;
use Cicada\Core\System\SystemConfig\SystemConfigCollection;

#[Package('frontend')]
class ChannelEntity extends Entity
{
    use EntityCustomFieldsTrait;
    use EntityIdTrait;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $typeId;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $languageId;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $currencyId;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $paymentMethodId;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $shippingMethodId;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $countryId;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $navigationCategoryId;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $navigationCategoryVersionId;

    /**
     * @var int
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $navigationCategoryDepth;

    /**
     * @var array<mixed>|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $homeSlotConfig;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $homeCmsPageId;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $homeCmsPageVersionId;

    /**
     * @var CmsPageEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $homeCmsPage;

    /**
     * @var bool
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $homeEnabled;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $homeName;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $homeMetaTitle;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $homeMetaDescription;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $homeKeywords;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $footerCategoryId;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $footerCategoryVersionId;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $serviceCategoryId;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $serviceCategoryVersionId;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $name;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $shortName;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $accessKey;

    /**
     * @var CurrencyCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $currencies;

    /**
     * @var LanguageCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $languages;

    /**
     * @var array<mixed>|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $configuration;

    /**
     * @var bool
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $active;

    /**
     * @var bool
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $maintenance;

    /**
     * @var array<mixed>|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $maintenanceIpWhitelist;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $taxCalculationType;

    /**
     * @var ChannelTypeEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $type;

    /**
     * @var CurrencyEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $currency;

    /**
     * @var LanguageEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $language;

    /**
     * @var PaymentMethodEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $paymentMethod;

    /**
     * @var ShippingMethodEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $shippingMethod;

    /**
     * @var CountryEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $country;

    /**
     * @var OrderCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $orders;

    /**
     * @var MemberCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $members;

    /**
     * @var CountryCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $countries;

    /**
     * @var PaymentMethodCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $paymentMethods;

    /**
     * @var ShippingMethodCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $shippingMethods;

    /**
     * @var ChannelTranslationCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $translations;

    /**
     * @var ChannelDomainCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $domains;

    /**
     * @var SystemConfigCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $systemConfigs;

    /**
     * @var CategoryEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $navigationCategory;

    /**
     * @var CategoryEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $footerCategory;

    /**
     * @var CategoryEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $serviceCategory;

    /**
     * @var ProductVisibilityCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $productVisibilities;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $mailHeaderFooterId;

    /**
     * @var NumberRangeChannelCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $numberRangeChannels;

    /**
     * @var MailHeaderFooterEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $mailHeaderFooter;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $memberGroupId;

    /**
     * @var MemberGroupEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $memberGroup;

    /**
     * @var NewsletterRecipientCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $newsletterRecipients;

    /**
     * @var PromotionChannelCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $promotionChannels;

    /**
     * @var DocumentBaseConfigDefinition|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $documentBaseConfigChannels;

    /**
     * @var ProductReviewCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $productReviews;

    /**
     * @var SeoUrlCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $seoUrls;

    /**
     * @var SeoUrlTemplateCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $seoUrlTemplates;

    /**
     * @var MainCategoryCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $mainCategories;

    /**
     * @var array<string>|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $paymentMethodIds;

    /**
     * @var ProductExportCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $productExports;

    /**
     * @var bool
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $hreflangActive;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $hreflangDefaultDomainId;

    /**
     * @var ChannelDomainEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $hreflangDefaultDomain;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $analyticsId;

    /**
     * @var ChannelAnalyticsEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $analytics;

    /**
     * @var MemberGroupCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $memberGroupsRegistrations;

    /**
     * @var MemberCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $boundMembers;

    /**
     * @var MemberWishlistCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $wishlists;

    /**
     * @var LandingPageCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $landingPages;

    public function getMailHeaderFooter(): ?MailHeaderFooterEntity
    {
        return $this->mailHeaderFooter;
    }

    public function setMailHeaderFooter(?MailHeaderFooterEntity $mailHeaderFooter): void
    {
        $this->mailHeaderFooter = $mailHeaderFooter;
    }

    public function getMailHeaderFooterId(): ?string
    {
        return $this->mailHeaderFooterId;
    }

    public function setMailHeaderFooterId(string $mailHeaderFooterId): void
    {
        $this->mailHeaderFooterId = $mailHeaderFooterId;
    }

    public function getLanguageId(): string
    {
        return $this->languageId;
    }

    public function setLanguageId(string $languageId): void
    {
        $this->languageId = $languageId;
    }

    public function getCurrencyId(): string
    {
        return $this->currencyId;
    }

    public function setCurrencyId(string $currencyId): void
    {
        $this->currencyId = $currencyId;
    }

    public function getPaymentMethodId(): string
    {
        return $this->paymentMethodId;
    }

    public function setPaymentMethodId(string $paymentMethodId): void
    {
        $this->paymentMethodId = $paymentMethodId;
    }

    public function getShippingMethodId(): string
    {
        return $this->shippingMethodId;
    }

    public function setShippingMethodId(string $shippingMethodId): void
    {
        $this->shippingMethodId = $shippingMethodId;
    }

    public function getCountryId(): string
    {
        return $this->countryId;
    }

    public function setCountryId(string $countryId): void
    {
        $this->countryId = $countryId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    public function setShortName(?string $shortName): void
    {
        $this->shortName = $shortName;
    }

    public function getAccessKey(): string
    {
        return $this->accessKey;
    }

    public function setAccessKey(string $accessKey): void
    {
        $this->accessKey = $accessKey;
    }

    public function getCurrencies(): ?CurrencyCollection
    {
        return $this->currencies;
    }

    public function setCurrencies(CurrencyCollection $currencies): void
    {
        $this->currencies = $currencies;
    }

    public function getLanguages(): ?LanguageCollection
    {
        return $this->languages;
    }

    public function setLanguages(LanguageCollection $languages): void
    {
        $this->languages = $languages;
    }

    /**
     * @return array<mixed>|null
     */
    public function getConfiguration(): ?array
    {
        return $this->configuration;
    }

    /**
     * @param array<mixed> $configuration
     */
    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function isMaintenance(): bool
    {
        return $this->maintenance;
    }

    public function setMaintenance(bool $maintenance): void
    {
        $this->maintenance = $maintenance;
    }

    /**
     * @return array<mixed>|null
     */
    public function getMaintenanceIpWhitelist(): ?array
    {
        return $this->maintenanceIpWhitelist;
    }

    /**
     * @param array<mixed>|null $maintenanceIpWhitelist
     */
    public function setMaintenanceIpWhitelist(?array $maintenanceIpWhitelist): void
    {
        $this->maintenanceIpWhitelist = $maintenanceIpWhitelist;
    }

    public function getCurrency(): ?CurrencyEntity
    {
        return $this->currency;
    }

    public function setCurrency(CurrencyEntity $currency): void
    {
        $this->currency = $currency;
    }

    public function getLanguage(): ?LanguageEntity
    {
        return $this->language;
    }

    public function setLanguage(LanguageEntity $language): void
    {
        $this->language = $language;
    }

    public function getPaymentMethod(): ?PaymentMethodEntity
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(PaymentMethodEntity $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getShippingMethod(): ?ShippingMethodEntity
    {
        return $this->shippingMethod;
    }

    public function setShippingMethod(ShippingMethodEntity $shippingMethod): void
    {
        $this->shippingMethod = $shippingMethod;
    }

    public function getCountry(): ?CountryEntity
    {
        return $this->country;
    }

    public function setCountry(CountryEntity $country): void
    {
        $this->country = $country;
    }

    public function getOrders(): ?OrderCollection
    {
        return $this->orders;
    }

    public function setOrders(OrderCollection $orders): void
    {
        $this->orders = $orders;
    }

    public function getMembers(): ?MemberCollection
    {
        return $this->members;
    }

    public function setMembers(MemberCollection $members): void
    {
        $this->members = $members;
    }

    public function getTypeId(): string
    {
        return $this->typeId;
    }

    public function setTypeId(string $typeId): void
    {
        $this->typeId = $typeId;
    }

    public function getType(): ?ChannelTypeEntity
    {
        return $this->type;
    }

    public function setType(ChannelTypeEntity $type): void
    {
        $this->type = $type;
    }

    public function getCountries(): ?CountryCollection
    {
        return $this->countries;
    }

    public function setCountries(CountryCollection $countries): void
    {
        $this->countries = $countries;
    }

    public function getTranslations(): ?ChannelTranslationCollection
    {
        return $this->translations;
    }

    public function setTranslations(ChannelTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }

    public function getPaymentMethods(): ?PaymentMethodCollection
    {
        return $this->paymentMethods;
    }

    public function setPaymentMethods(PaymentMethodCollection $paymentMethods): void
    {
        $this->paymentMethods = $paymentMethods;
    }

    public function getShippingMethods(): ?ShippingMethodCollection
    {
        return $this->shippingMethods;
    }

    public function setShippingMethods(ShippingMethodCollection $shippingMethods): void
    {
        $this->shippingMethods = $shippingMethods;
    }

    public function getDomains(): ?ChannelDomainCollection
    {
        return $this->domains;
    }

    public function setDomains(ChannelDomainCollection $domains): void
    {
        $this->domains = $domains;
    }

    public function getSystemConfigs(): ?SystemConfigCollection
    {
        return $this->systemConfigs;
    }

    public function setSystemConfigs(SystemConfigCollection $systemConfigs): void
    {
        $this->systemConfigs = $systemConfigs;
    }

    public function getNavigationCategoryId(): string
    {
        return $this->navigationCategoryId;
    }

    public function setNavigationCategoryId(string $navigationCategoryId): void
    {
        $this->navigationCategoryId = $navigationCategoryId;
    }

    public function getNavigationCategory(): ?CategoryEntity
    {
        return $this->navigationCategory;
    }

    public function setNavigationCategory(CategoryEntity $navigationCategory): void
    {
        $this->navigationCategory = $navigationCategory;
    }

    /**
     * @return array<mixed>|null
     */
    public function getHomeSlotConfig(): ?array
    {
        return $this->homeSlotConfig;
    }

    /**
     * @param array<mixed>|null $homeSlotConfig
     */
    public function setHomeSlotConfig(?array $homeSlotConfig): void
    {
        $this->homeSlotConfig = $homeSlotConfig;
    }

    public function getHomeCmsPageId(): ?string
    {
        return $this->homeCmsPageId;
    }

    public function setHomeCmsPageId(?string $homeCmsPageId): void
    {
        $this->homeCmsPageId = $homeCmsPageId;
    }

    public function getHomeCmsPage(): ?CmsPageEntity
    {
        return $this->homeCmsPage;
    }

    public function setHomeCmsPage(?CmsPageEntity $homeCmsPage): void
    {
        $this->homeCmsPage = $homeCmsPage;
    }

    public function getHomeEnabled(): bool
    {
        return $this->homeEnabled;
    }

    public function setHomeEnabled(bool $homeEnabled): void
    {
        $this->homeEnabled = $homeEnabled;
    }

    public function getHomeName(): ?string
    {
        return $this->homeName;
    }

    public function setHomeName(?string $homeName): void
    {
        $this->homeName = $homeName;
    }

    public function getHomeMetaTitle(): ?string
    {
        return $this->homeMetaTitle;
    }

    public function setHomeMetaTitle(?string $homeMetaTitle): void
    {
        $this->homeMetaTitle = $homeMetaTitle;
    }

    public function getHomeMetaDescription(): ?string
    {
        return $this->homeMetaDescription;
    }

    public function setHomeMetaDescription(?string $homeMetaDescription): void
    {
        $this->homeMetaDescription = $homeMetaDescription;
    }

    public function getHomeKeywords(): ?string
    {
        return $this->homeKeywords;
    }

    public function setHomeKeywords(?string $homeKeywords): void
    {
        $this->homeKeywords = $homeKeywords;
    }

    public function getProductVisibilities(): ?ProductVisibilityCollection
    {
        return $this->productVisibilities;
    }

    public function setProductVisibilities(ProductVisibilityCollection $productVisibilities): void
    {
        $this->productVisibilities = $productVisibilities;
    }

    public function getMemberGroupId(): string
    {
        return $this->memberGroupId;
    }

    public function setMemberGroupId(string $memberGroupId): void
    {
        $this->memberGroupId = $memberGroupId;
    }

    public function getMemberGroup(): ?MemberGroupEntity
    {
        return $this->memberGroup;
    }

    public function setMemberGroup(MemberGroupEntity $memberGroup): void
    {
        $this->memberGroup = $memberGroup;
    }

    public function getNewsletterRecipients(): ?NewsletterRecipientCollection
    {
        return $this->newsletterRecipients;
    }

    public function setNewsletterRecipients(NewsletterRecipientCollection $newsletterRecipients): void
    {
        $this->newsletterRecipients = $newsletterRecipients;
    }

    public function getPromotionChannels(): ?PromotionChannelCollection
    {
        return $this->promotionChannels;
    }

    public function setPromotionChannels(PromotionChannelCollection $promotionChannels): void
    {
        $this->promotionChannels = $promotionChannels;
    }

    public function getNumberRangeChannels(): ?NumberRangeChannelCollection
    {
        return $this->numberRangeChannels;
    }

    public function setNumberRangeChannels(NumberRangeChannelCollection $numberRangeChannels): void
    {
        $this->numberRangeChannels = $numberRangeChannels;
    }

    public function getFooterCategoryId(): ?string
    {
        return $this->footerCategoryId;
    }

    public function setFooterCategoryId(string $footerCategoryId): void
    {
        $this->footerCategoryId = $footerCategoryId;
    }

    public function getServiceCategoryId(): ?string
    {
        return $this->serviceCategoryId;
    }

    public function setServiceCategoryId(string $serviceCategoryId): void
    {
        $this->serviceCategoryId = $serviceCategoryId;
    }

    public function getFooterCategory(): ?CategoryEntity
    {
        return $this->footerCategory;
    }

    public function setFooterCategory(CategoryEntity $footerCategory): void
    {
        $this->footerCategory = $footerCategory;
    }

    public function getServiceCategory(): ?CategoryEntity
    {
        return $this->serviceCategory;
    }

    public function setServiceCategory(CategoryEntity $serviceCategory): void
    {
        $this->serviceCategory = $serviceCategory;
    }

    public function getDocumentBaseConfigChannels(): ?DocumentBaseConfigDefinition
    {
        return $this->documentBaseConfigChannels;
    }

    public function setDocumentBaseConfigChannels(DocumentBaseConfigDefinition $documentBaseConfigChannels): void
    {
        $this->documentBaseConfigChannels = $documentBaseConfigChannels;
    }

    public function getProductReviews(): ?ProductReviewCollection
    {
        return $this->productReviews;
    }

    public function setProductReviews(ProductReviewCollection $productReviews): void
    {
        $this->productReviews = $productReviews;
    }

    public function getSeoUrls(): ?SeoUrlCollection
    {
        return $this->seoUrls;
    }

    public function setSeoUrls(SeoUrlCollection $seoUrls): void
    {
        $this->seoUrls = $seoUrls;
    }

    public function getSeoUrlTemplates(): ?SeoUrlTemplateCollection
    {
        return $this->seoUrlTemplates;
    }

    public function setSeoUrlTemplates(SeoUrlTemplateCollection $seoUrlTemplates): void
    {
        $this->seoUrlTemplates = $seoUrlTemplates;
    }

    public function getMainCategories(): ?MainCategoryCollection
    {
        return $this->mainCategories;
    }

    public function setMainCategories(MainCategoryCollection $mainCategories): void
    {
        $this->mainCategories = $mainCategories;
    }

    /**
     * @return array<string>|null
     */
    public function getPaymentMethodIds(): ?array
    {
        return $this->paymentMethodIds;
    }

    /**
     * @param array<string> $paymentMethodIds
     */
    public function setPaymentMethodIds(array $paymentMethodIds): void
    {
        $this->paymentMethodIds = $paymentMethodIds;
    }

    public function getProductExports(): ?ProductExportCollection
    {
        return $this->productExports;
    }

    public function setProductExports(ProductExportCollection $productExports): void
    {
        $this->productExports = $productExports;
    }

    public function getNavigationCategoryDepth(): int
    {
        return $this->navigationCategoryDepth;
    }

    public function setNavigationCategoryDepth(int $navigationCategoryDepth): void
    {
        $this->navigationCategoryDepth = $navigationCategoryDepth;
    }

    public function isHreflangActive(): bool
    {
        return $this->hreflangActive;
    }

    public function setHreflangActive(bool $hreflangActive): void
    {
        $this->hreflangActive = $hreflangActive;
    }

    public function getHreflangDefaultDomainId(): ?string
    {
        return $this->hreflangDefaultDomainId;
    }

    public function setHreflangDefaultDomainId(?string $hreflangDefaultDomainId): void
    {
        $this->hreflangDefaultDomainId = $hreflangDefaultDomainId;
    }

    public function getHreflangDefaultDomain(): ?ChannelDomainEntity
    {
        return $this->hreflangDefaultDomain;
    }

    public function setHreflangDefaultDomain(?ChannelDomainEntity $hreflangDefaultDomain): void
    {
        $this->hreflangDefaultDomain = $hreflangDefaultDomain;
    }

    public function getAnalyticsId(): ?string
    {
        return $this->analyticsId;
    }

    public function setAnalyticsId(?string $analyticsId): void
    {
        $this->analyticsId = $analyticsId;
    }

    public function getAnalytics(): ?ChannelAnalyticsEntity
    {
        return $this->analytics;
    }

    public function setAnalytics(?ChannelAnalyticsEntity $analytics): void
    {
        $this->analytics = $analytics;
    }

    public function getTaxCalculationType(): string
    {
        return $this->taxCalculationType;
    }

    public function setTaxCalculationType(string $taxCalculationType): void
    {
        $this->taxCalculationType = $taxCalculationType;
    }

    public function getMemberGroupsRegistrations(): ?MemberGroupCollection
    {
        return $this->memberGroupsRegistrations;
    }

    public function setMemberGroupsRegistrations(MemberGroupCollection $memberGroupsRegistrations): void
    {
        $this->memberGroupsRegistrations = $memberGroupsRegistrations;
    }

    public function getBoundMembers(): ?MemberCollection
    {
        return $this->boundMembers;
    }

    public function setBoundMembers(MemberCollection $boundMembers): void
    {
        $this->boundMembers = $boundMembers;
    }

    public function getWishlists(): ?MemberWishlistCollection
    {
        return $this->wishlists;
    }

    public function setWishlists(MemberWishlistCollection $wishlists): void
    {
        $this->wishlists = $wishlists;
    }

    public function getLandingPages(): ?LandingPageCollection
    {
        return $this->landingPages;
    }

    public function setLandingPages(LandingPageCollection $landingPages): void
    {
        $this->landingPages = $landingPages;
    }

    public function getNavigationCategoryVersionId(): string
    {
        return $this->navigationCategoryVersionId;
    }

    public function setNavigationCategoryVersionId(string $navigationCategoryVersionId): void
    {
        $this->navigationCategoryVersionId = $navigationCategoryVersionId;
    }

    public function getHomeCmsPageVersionId(): ?string
    {
        return $this->homeCmsPageVersionId;
    }

    public function setHomeCmsPageVersionId(?string $homeCmsPageVersionId): void
    {
        $this->homeCmsPageVersionId = $homeCmsPageVersionId;
    }

    public function getFooterCategoryVersionId(): ?string
    {
        return $this->footerCategoryVersionId;
    }

    public function setFooterCategoryVersionId(?string $footerCategoryVersionId): void
    {
        $this->footerCategoryVersionId = $footerCategoryVersionId;
    }

    public function getServiceCategoryVersionId(): ?string
    {
        return $this->serviceCategoryVersionId;
    }

    public function setServiceCategoryVersionId(?string $serviceCategoryVersionId): void
    {
        $this->serviceCategoryVersionId = $serviceCategoryVersionId;
    }
}