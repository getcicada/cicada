<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Twig;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\PlatformRequest;
use Cicada\Core\ChannelRequest;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

#[Package('frontend')]
class TemplateDataExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly bool $showStagingBanner
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getGlobals(): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return [];
        }

        /** @var ChannelContext|null $context */
        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

        if (!$context) {
            return [];
        }

        [$controllerName, $controllerAction] = $this->getControllerInfo($request);

        $themeId = $request->attributes->get(ChannelRequest::ATTRIBUTE_THEME_ID);

        return [
            'cicada' => [
                'dateFormat' => \DATE_ATOM,
            ],
            'themeId' => $themeId,
            'controllerName' => $controllerName,
            'controllerAction' => $controllerAction,
            'context' => $context,
            'activeRoute' => $request->attributes->get('_route'),
            'formViolations' => $request->attributes->get('formViolations'),
            'showStagingBanner' => $this->showStagingBanner,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function getControllerInfo(Request $request): array
    {
        $controller = $request->attributes->get('_controller');

        if (!$controller) {
            return ['', ''];
        }

        $matches = [];
        preg_match('/Controller\\\\(\w+)Controller::?(\w+)$/', (string) $controller, $matches);

        if ($matches) {
            return [$matches[1], $matches[2]];
        }

        return ['', ''];
    }
}
