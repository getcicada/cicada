<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Twig;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Frontend\Theme\ThemeConfigValueAccessor;
use Cicada\Frontend\Theme\ThemeScripts;

#[Package('frontend')]
class TemplateConfigAccessor
{
    /**
     * @internal
     */
    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly ThemeConfigValueAccessor $themeConfigAccessor,
        private readonly ThemeScripts $themeScripts
    ) {
    }

    /**
     * @return string|bool|array<mixed>|float|int|null
     */
    public function config(string $key, ?string $channelId)
    {
        $static = $this->getStatic();

        if (\array_key_exists($key, $static)) {
            return $static[$key];
        }

        return $this->systemConfigService->get($key, $channelId);
    }

    /**
     * @return string|bool|array<string, mixed>|float|int|null
     */
    public function theme(string $key, ChannelContext $context, ?string $themeId)
    {
        return $this->themeConfigAccessor->get($key, $context, $themeId);
    }

    /**
     * @return array<int, string> $items
     */
    public function scripts(): array
    {
        return $this->themeScripts->getThemeScripts();
    }

    /**
     * @return array<string, int|string|bool> $items
     */
    private function getStatic(): array
    {
        return [
            'seo.descriptionMaxLength' => 255,
            'cms.revocationNoticeCmsPageId' => '00B9A8636F954277AE424E6C1C36A1F5',
            'cms.taxCmsPageId' => '00B9A8636F954277AE424E6C1C36A1F5',
            'cms.tosCmsPageId' => '00B9A8636F954277AE424E6C1C36A1F5',
            'confirm.revocationNotice' => true,
        ];
    }
}
