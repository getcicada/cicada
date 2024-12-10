<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme\Twig;

use Cicada\Core\Checkout\Document\Event\DocumentTemplateRendererParameterEvent;
use Cicada\Core\Framework\Adapter\Twig\NamespaceHierarchy\TemplateNamespaceHierarchyBuilderInterface;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\ChannelRequest;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Frontend\Theme\DatabaseChannelThemeLoader;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal
 */
#[Package('frontend')]
class ThemeNamespaceHierarchyBuilder implements TemplateNamespaceHierarchyBuilderInterface, EventSubscriberInterface, ResetInterface
{
    /**
     * @var array<int|string, bool>
     */
    private array $themes = [];

    /**
     * @internal
     */
    public function __construct(
        private readonly ThemeInheritanceBuilderInterface $themeInheritanceBuilder,
        private readonly ?DatabaseChannelThemeLoader $channelThemeLoader = null
    ) {
    }

    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'requestEvent',
            KernelEvents::EXCEPTION => 'requestEvent',
            DocumentTemplateRendererParameterEvent::class => 'onDocumentRendering',
        ];
    }

    public function requestEvent(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $this->themes = $this->detectedThemes($request);
    }

    public function onDocumentRendering(DocumentTemplateRendererParameterEvent $event): void
    {
        $parameters = $event->getParameters();

        if (!\array_key_exists('context', $parameters)) {
            return;
        }

        /** @var ChannelContext $context */
        $context = $parameters['context'];

        $themes = [];

        $theme = $this->channelThemeLoader?->load($context->getChannelId());

        if (empty($theme) || !isset($theme[0])) {
            return;
        }

        $themes[$theme[0]] = true;
        $themes['Frontend'] = true;

        $this->themes = $themes;
    }

    public function buildNamespaceHierarchy(array $namespaceHierarchy): array
    {
        if (empty($this->themes)) {
            return $namespaceHierarchy;
        }

        return $this->themeInheritanceBuilder->build($namespaceHierarchy, $this->themes);
    }

    public function reset(): void
    {
        $this->themes = [];
    }

    /**
     * @return array<int|string, bool>
     */
    private function detectedThemes(Request $request): array
    {
        $themes = [];
        // get name if theme is not inherited
        $theme = $request->attributes->get(ChannelRequest::ATTRIBUTE_THEME_NAME);

        if (!$theme) {
            // get theme name from base theme because for inherited themes the name is always null
            $theme = $request->attributes->get(ChannelRequest::ATTRIBUTE_THEME_BASE_NAME);
        }

        if (!$theme) {
            return [];
        }

        $themes[$theme] = true;
        $themes['Frontend'] = true;

        return $themes;
    }
}
