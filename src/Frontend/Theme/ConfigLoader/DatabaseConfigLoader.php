<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme\ConfigLoader;

use Cicada\Core\Content\Media\MediaCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Frontend\Theme\Exception\ThemeException;
use Cicada\Frontend\Theme\FrontendPluginConfiguration\FrontendPluginConfiguration;
use Cicada\Frontend\Theme\FrontendPluginRegistry;
use Cicada\Frontend\Theme\ThemeCollection;
use Cicada\Frontend\Theme\ThemeConfigField;
use Cicada\Frontend\Theme\ThemeEntity;

#[Package('frontend')]
class DatabaseConfigLoader extends AbstractConfigLoader
{
    /**
     * @internal
     *
     * @param EntityRepository<ThemeCollection> $themeRepository
     * @param EntityRepository<MediaCollection> $mediaRepository
     */
    public function __construct(
        private readonly EntityRepository $themeRepository,
        private readonly FrontendPluginRegistry $extensionRegistry,
        private readonly EntityRepository $mediaRepository,
        private readonly string $baseTheme = FrontendPluginRegistry::BASE_THEME_NAME
    ) {
    }

    public function getDecorated(): AbstractConfigLoader
    {
        throw new DecorationPatternException(self::class);
    }

    public function load(string $themeId, Context $context): FrontendPluginConfiguration
    {
        $pluginConfig = $this->loadConfigByName($themeId, $context);

        if (!$pluginConfig) {
            throw ThemeException::couldNotFindThemeById($themeId);
        }

        $pluginConfig = clone $pluginConfig;

        $config = $this->loadCompileConfig($themeId, $context);

        $pluginConfig->setThemeConfig($config);

        $this->resolveMediaIds($pluginConfig, $context);

        return $pluginConfig;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadCompileConfig(string $themeId, Context $context): array
    {
        $config = $this->loadRecursiveConfig($themeId, $context);

        $field = new ThemeConfigField();

        foreach ($config['fields'] as $name => $item) {
            $clone = clone $field;
            $clone->setName($name);
            $clone->assign($item);
            $config[$name] = $clone;
        }

        return json_decode((string) json_encode($config, \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadRecursiveConfig(string $themeId, Context $context, bool $withBase = true): array
    {
        $criteria = new Criteria();
        $criteria->setTitle('theme-service::load-config');

        $themes = $this->themeRepository->search($criteria, $context)->getEntities();

        $theme = $themes->get($themeId);
        if (!$theme) {
            throw ThemeException::couldNotFindThemeById($themeId);
        }
        $baseThemeConfig = [];

        if ($withBase) {
            $baseTheme = $themes->filter(fn (ThemeEntity $themeEntry) => $themeEntry->getTechnicalName() === $this->baseTheme)->first();
            \assert($baseTheme !== null);

            $baseThemeConfig = $this->mergeStaticConfig($baseTheme);
        }

        if ($theme->getParentThemeId()) {
            $parentThemes = $this->getParentThemeIds($themes, $theme);

            foreach ($parentThemes as $parentTheme) {
                $configuredParentTheme = $this->mergeStaticConfig($parentTheme);
                $baseThemeConfig = array_replace_recursive($baseThemeConfig, $configuredParentTheme);
            }
        }

        $configuredTheme = $this->mergeStaticConfig($theme);

        return array_replace_recursive($baseThemeConfig, $configuredTheme);
    }

    /**
     * @param array<string, ThemeEntity> $parentThemes
     *
     * @return array<string, ThemeEntity>
     */
    private function getParentThemeIds(ThemeCollection $themes, ThemeEntity $mainTheme, array $parentThemes = []): array
    {
        // add configured parent themes
        foreach ($this->getConfigInheritance($mainTheme) as $parentThemeName) {
            $parentTheme = $themes->filter(fn (ThemeEntity $themeEntry) => $themeEntry->getTechnicalName() === str_replace('@', '', $parentThemeName))->first();

            if (!($parentTheme instanceof ThemeEntity)) {
                continue;
            }

            if (\array_key_exists($parentTheme->getId(), $parentThemes)) {
                continue;
            }

            $parentThemes[$parentTheme->getId()] = $parentTheme;
            if ($parentTheme->getParentThemeId()) {
                $parentThemes = $this->getParentThemeIds($themes, $mainTheme, $parentThemes);
            }
        }

        if ($mainTheme->getParentThemeId() === null) {
            return $parentThemes;
        }

        // add database defined parent theme
        $parentTheme = $themes->filter(fn (ThemeEntity $themeEntry) => $themeEntry->getId() === $mainTheme->getParentThemeId())->first();

        if (!($parentTheme instanceof ThemeEntity)) {
            return $parentThemes;
        }

        if (\array_key_exists($parentTheme->getId(), $parentThemes)) {
            return $parentThemes;
        }

        $parentThemes[$parentTheme->getId()] = $parentTheme;
        if ($parentTheme->getParentThemeId()) {
            $parentThemes = $this->getParentThemeIds($themes, $mainTheme, $parentThemes);
        }

        return $parentThemes;
    }

    private function loadConfigByName(string $themeId, Context $context): ?FrontendPluginConfiguration
    {
        /** @var ThemeEntity|null $theme */
        $theme = $this->themeRepository
            ->search(new Criteria([$themeId]), $context)
            ->get($themeId);

        if ($theme === null) {
            return $this->extensionRegistry
                ->getConfigurations()
                ->getByTechnicalName($this->baseTheme);
        }

        $pluginConfig = null;
        if ($theme->getTechnicalName() !== null) {
            $pluginConfig = $this->extensionRegistry
                ->getConfigurations()
                ->getByTechnicalName($theme->getTechnicalName());
        }

        if ($pluginConfig !== null) {
            return $pluginConfig;
        }

        if ($theme->getParentThemeId() !== null) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('id', $theme->getParentThemeId()));

            /** @var ThemeEntity $parentTheme */
            $parentTheme = $this->themeRepository
                ->search($criteria, $context)
                ->first();

            if (!\is_string($parentTheme->getTechnicalName())) {
                return $this->extensionRegistry
                    ->getConfigurations()
                    ->getByTechnicalName($this->baseTheme);
            }

            return $this->extensionRegistry
                ->getConfigurations()
                ->getByTechnicalName($parentTheme->getTechnicalName());
        }

        return $this->extensionRegistry
            ->getConfigurations()
            ->getByTechnicalName($this->baseTheme);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function mergeStaticConfig(ThemeEntity $theme): array
    {
        $configuredTheme = [];

        $pluginConfig = null;
        if ($theme->getTechnicalName()) {
            $pluginConfig = $this->extensionRegistry->getConfigurations()->getByTechnicalName($theme->getTechnicalName());
        }

        if ($pluginConfig !== null) {
            $configuredTheme = $pluginConfig->getThemeConfig() ?? [];
        }

        if ($theme->getBaseConfig() !== null) {
            $configuredTheme = array_replace_recursive($configuredTheme, $theme->getBaseConfig());
        }

        if ($theme->getConfigValues() === null) {
            return $configuredTheme;
        }

        foreach ($theme->getConfigValues() as $fieldName => $configValue) {
            if (isset($configValue['value'])) {
                $configuredTheme['fields'][$fieldName]['value'] = $configValue['value'];
            }
        }

        return $configuredTheme;
    }

    private function resolveMediaIds(FrontendPluginConfiguration $pluginConfig, Context $context): void
    {
        $config = $pluginConfig->getThemeConfig();

        if (!\is_array($config)) {
            return;
        }

        $ids = [];

        // Collect all ids
        foreach ($config['fields'] as $_ => $data) {
            if (!isset($data['value'])
                || $data['value'] === ''
                || !\is_string($data['value'])
                || (\array_key_exists('scss', $data) && $data['scss'] === false)
                || (isset($data['type']) && $data['type'] !== 'media')
                || !Uuid::isValid($data['value'])
            ) {
                continue;
            }

            $ids[] = $data['value'];
        }

        if (\count($ids) === 0) {
            return;
        }

        $criteria = new Criteria($ids);

        $mediaResult = $this->mediaRepository->search($criteria, $context)->getEntities();

        // Replace all ids with the actual url
        foreach ($config['fields'] as $key => $data) {
            if (!isset($data['value']) || !\is_string($data['value'])) {
                continue;
            }

            if (
                $data['value'] === ''
                || (\array_key_exists('scss', $data) && $data['scss'] === false)
                || (isset($data['type']) && $data['type'] !== 'media')
                || !Uuid::isValid($data['value'])
                || !$mediaResult->has($data['value'])
            ) {
                continue;
            }

            $config['fields'][$key]['value'] = $mediaResult->get($data['value'])->getUrl();
        }

        $pluginConfig->setThemeConfig($config);
    }

    /**
     * @return array<int, string>
     */
    private function getConfigInheritance(ThemeEntity $mainTheme): array
    {
        if (!\is_array($mainTheme->getBaseConfig())) {
            return [];
        }

        if (!\array_key_exists('configInheritance', $mainTheme->getBaseConfig())) {
            return [];
        }

        if (!\is_array($mainTheme->getBaseConfig()['configInheritance'])) {
            return [];
        }

        if (empty($mainTheme->getBaseConfig()['configInheritance'])) {
            return [];
        }

        return $mainTheme->getBaseConfig()['configInheritance'];
    }
}
