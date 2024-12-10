<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Services;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\TermsResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\PluginCollection;
use Cicada\Core\Framework\Plugin\PluginEntity;
use Cicada\Core\Framework\Store\Authentication\LocaleProvider;
use Cicada\Core\Framework\Store\Struct\BinaryCollection;
use Cicada\Core\Framework\Store\Struct\ExtensionCollection;
use Cicada\Core\Framework\Store\Struct\ExtensionStruct;
use Cicada\Core\Framework\Store\Struct\FaqCollection;
use Cicada\Core\Framework\Store\Struct\ImageCollection;
use Cicada\Core\Framework\Store\Struct\PermissionCollection;
use Cicada\Core\Framework\Store\Struct\StoreCategoryCollection;
use Cicada\Core\Framework\Store\Struct\StoreCollection;
use Cicada\Core\Framework\Store\Struct\VariantCollection;
use Cicada\Core\System\Locale\LanguageLocaleCodeProvider;
use Cicada\Core\System\SystemConfig\Service\ConfigurationService;
use Cicada\Frontend\Framework\ThemeInterface;
use Symfony\Component\Intl\Languages;
use Symfony\Component\Intl\Locales;

/**
 * @internal
 */
#[Package('member')]
class ExtensionLoader
{
    private const DEFAULT_LOCALE = 'zh_CN';

    /**
     * @var array<string>|null
     */
    private ?array $installedThemeNames = null;

    public function __construct(
        private readonly ?EntityRepository $themeRepository,
        private readonly ConfigurationService $configurationService,
        private readonly LocaleProvider $localeProvider,
        private readonly LanguageLocaleCodeProvider $languageLocaleProvider
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function loadFromArray(Context $context, array $data, ?string $locale = null): ExtensionStruct
    {
        if ($locale === null) {
            $locale = $this->localeProvider->getLocaleFromContext($context);
        }

        $localeWithUnderscore = str_replace('-', '_', $locale);
        $data = $this->prepareArrayData($data, $localeWithUnderscore);

        return ExtensionStruct::fromArray($data);
    }

    /**
     * @param array<array<string, mixed>> $data
     */
    public function loadFromListingArray(Context $context, array $data): ExtensionCollection
    {
        $locale = $this->localeProvider->getLocaleFromContext($context);
        $localeWithUnderscore = str_replace('-', '_', $locale);
        $extensions = new ExtensionCollection();

        foreach ($data as $extension) {
            $extension = ExtensionStruct::fromArray($this->prepareArrayData($extension, $localeWithUnderscore));
            $extensions->set($extension->getName(), $extension);
        }

        return $extensions;
    }


    public function loadFromPluginCollection(Context $context, PluginCollection $collection): ExtensionCollection
    {
        $extensions = new ExtensionCollection();

        foreach ($collection as $app) {
            $plugin = $this->loadFromPlugin($context, $app);
            $extensions->set($plugin->getName(), $plugin);
        }

        return $extensions;
    }

    public function getLocaleCodeFromLanguageId(Context $context, ?string $languageId = null): ?string
    {
        if ($languageId === null) {
            $languageId = $context->getLanguageId();
        }

        $id = $this->getLocalesCodesFromLanguageIds([$languageId]);

        if (empty($id)) {
            return null;
        }

        return $id[0];
    }

    /**
     * @param array<string> $languageIds
     *
     * @return array<string>
     */
    public function getLocalesCodesFromLanguageIds(array $languageIds): array
    {
        $codes = array_values($this->languageLocaleProvider->getLocalesForLanguageIds($languageIds));
        sort($codes);

        return array_map(static fn (string $locale): string => str_replace('-', '_', $locale), $codes);
    }

    private function loadFromPlugin(Context $context, PluginEntity $plugin): ExtensionStruct
    {
        $isTheme = false;

        if (interface_exists(ThemeInterface::class) && class_exists($plugin->getBaseClass())) {
            $implementedInterfaces = class_implements($plugin->getBaseClass());

            if (\is_array($implementedInterfaces)) {
                $isTheme = \array_key_exists(ThemeInterface::class, $implementedInterfaces);
            }
        }

        $data = [
            'localId' => $plugin->getId(),
            'description' => $plugin->getTranslation('description'),
            'name' => $plugin->getName(),
            'label' => $plugin->getTranslation('label'),
            'producerName' => $plugin->getAuthor(),
            'license' => $plugin->getLicense(),
            'version' => $plugin->getVersion(),
            'latestVersion' => $plugin->getUpgradeVersion(),
            'iconRaw' => $plugin->getIcon(),
            'installedAt' => $plugin->getInstalledAt(),
            'active' => $plugin->getActive(),
            'type' => ExtensionStruct::EXTENSION_TYPE_PLUGIN,
            'isTheme' => $isTheme,
            'configurable' => $this->configurationService->checkConfiguration(\sprintf('%s.config', $plugin->getName()), $context),
            'updatedAt' => $plugin->getUpgradedAt(),
            'allowDisable' => true,
            'managedByComposer' => $plugin->getManagedByComposer(),
        ];

        return ExtensionStruct::fromArray($this->replaceCollections($data));
    }

    /**
     * @return array<string>
     */
    private function getInstalledThemeNames(Context $context): array
    {
        if ($this->installedThemeNames === null && $this->themeRepository instanceof EntityRepository) {
            $themeNameAggregationName = 'theme_names';
            $criteria = new Criteria();
            $criteria->addAggregation(new TermsAggregation($themeNameAggregationName, 'technicalName'));

            /** @var TermsResult $themeNameAggregation */
            $themeNameAggregation = $this->themeRepository->aggregate($criteria, $context)->get($themeNameAggregationName);

            return $this->installedThemeNames = $themeNameAggregation->getKeys();
        }

        return $this->installedThemeNames ?? [];
    }
    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, StoreCollection|mixed|null>
     */
    private function prepareArrayData(array $data, ?string $locale): array
    {
        return $this->translateExtensionLanguages($this->replaceCollections($data), $locale);
    }
    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, StoreCollection|mixed|null>
     */
    private function replaceCollections(array $data): array
    {
        $replacements = [
            'variants' => VariantCollection::class,
            'faq' => FaqCollection::class,
            'binaries' => BinaryCollection::class,
            'images' => ImageCollection::class,
            'categories' => StoreCategoryCollection::class,
            'permissions' => PermissionCollection::class,
        ];

        foreach ($replacements as $key => $collectionClass) {
            $data[$key] = new $collectionClass($data[$key] ?? []);
        }

        return $data;
    }

    /**
     * @param array<string, StoreCollection|mixed|null> $data
     *
     * @return array<string, StoreCollection|mixed|null>
     */
    private function translateExtensionLanguages(array $data, ?string $locale = self::DEFAULT_LOCALE): array
    {
        if (!isset($data['languages'])) {
            return $data;
        }

        $locale = $locale && Locales::exists($locale) ? $locale : self::DEFAULT_LOCALE;

        foreach ($data['languages'] as $key => $language) {
            $data['languages'][$key] = Languages::getName($language['name'], $locale);
        }

        return $data;
    }
}
