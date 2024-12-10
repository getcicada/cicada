<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Twig\Extension;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Core\System\Channel\ChannelEntity;
use Cicada\Frontend\Framework\FrontendFrameworkException;
use Cicada\Frontend\Framework\Twig\TemplateConfigAccessor;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

#[Package('frontend')]
class ConfigExtension extends AbstractExtension
{
    /**
     * @internal
     */
    public function __construct(private readonly TemplateConfigAccessor $config)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('config', $this->config(...), ['needs_context' => true]),
            new TwigFunction('theme_config', $this->theme(...), ['needs_context' => true]),
            new TwigFunction('theme_scripts', $this->scripts(...), ['needs_context' => true]),
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return string|bool|array<mixed>|float|int|null
     */
    public function config(array $context, string $key)
    {
        return $this->config->config($key, $this->getChannelId($context));
    }

    /**
     * @param array<string, ChannelContext|string> $context
     *
     * @return string|bool|array<string, mixed>|float|int|null
     */
    public function theme(array $context, string $key)
    {
        return $this->config->theme($key, $this->getContext($context), $this->getThemeId($context));
    }

    /**
     * @return array<int, string> $items
     */
    public function scripts(): array
    {
        return $this->config->scripts();
    }

    /**
     * @param array<string, mixed> $context
     */
    private function getChannelId(array $context): ?string
    {
        if (isset($context['context'])) {
            $channelContext = $context['context'];
            if ($channelContext instanceof ChannelContext) {
                return $channelContext->getChannelId();
            }
        }
        if (isset($context['channel'])) {
            $channel = $context['channel'];
            if ($channel instanceof ChannelEntity) {
                return $channel->getId();
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function getThemeId(array $context): ?string
    {
        return $context['themeId'] ?? null;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function getContext(array $context): ChannelContext
    {
        if (!isset($context['context'])) {
            throw FrontendFrameworkException::channelContextObjectNotFound();
        }

        $context = $context['context'];

        if (!$context instanceof ChannelContext) {
            throw FrontendFrameworkException::channelContextObjectNotFound();
        }

        return $context;
    }
}
