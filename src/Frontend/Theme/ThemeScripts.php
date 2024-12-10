<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\PlatformRequest;
use Cicada\Core\ChannelRequest;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Frontend\Theme\ConfigLoader\AbstractConfigLoader;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @internal
 */
#[Package('frontend')]
readonly class ThemeScripts
{
    /**
     * @internal
     */
    public function __construct(
        private FrontendPluginRegistryInterface $pluginRegistry,
        private ThemeFileResolver $themeFileResolver,
        private RequestStack $requestStack,
        private AbstractThemePathBuilder $themePathBuilder,
        private CacheInterface $cache,
        private AbstractConfigLoader $configLoader,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function getThemeScripts(): array
    {
        $request = $this->requestStack->getMainRequest();

        if ($request === null) {
            return [];
        }

        $themeName = $request->attributes->get(ChannelRequest::ATTRIBUTE_THEME_NAME, ChannelRequest::ATTRIBUTE_THEME_BASE_NAME)
            ?? $request->attributes->get(ChannelRequest::ATTRIBUTE_THEME_BASE_NAME);

        $themeId = $request->attributes->get(ChannelRequest::ATTRIBUTE_THEME_ID);

        if ($themeName === null || $themeId === null) {
            return [];
        }

        $channelId = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID);
        $path = $this->themePathBuilder->assemblePath($channelId, $themeId);

        $channelContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

        if (!$channelContext instanceof ChannelContext) {
            return [];
        }

        return $this->cache->get('theme_scripts_' . $path, function (ItemInterface $item) use ($themeId, $channelContext) {
            $themeConfig = $this->configLoader->load($themeId, $channelContext->getContext());

            $resolvedFiles = $this->themeFileResolver->resolveFiles(
                $themeConfig,
                $this->pluginRegistry->getConfigurations(),
                false
            );

            return $resolvedFiles[ThemeFileResolver::SCRIPT_FILES]->getPublicPaths('js');
        });
    }
}
