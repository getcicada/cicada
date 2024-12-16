<?php declare(strict_types=1);

namespace Cicada\Administration\Controller;

use Cicada\Administration\Framework\Routing\KnownIps\KnownIpsCollectorInterface;
use Cicada\Administration\Snippet\SnippetFinderInterface;
use Cicada\Core\Defaults;
use Cicada\Core\DevOps\Environment\EnvironmentHelper;
use Cicada\Core\Framework\Adapter\Twig\TemplateFinder;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\Framework\Store\Services\FirstRunWizardService;
use Cicada\Core\Framework\Util\HtmlSanitizer;
use Cicada\Core\PlatformRequest;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Doctrine\DBAL\Connection;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route(defaults: ['_routeScope' => ['administration']])]
#[Package('administration')]
class AdministrationController extends AbstractController
{
    /**
     * @internal
     *
     * @param array<int, int> $supportedApiVersions
     */
    public function __construct(
        private readonly TemplateFinder $finder,
        private readonly FirstRunWizardService $firstRunWizardService,
        private readonly SnippetFinderInterface $snippetFinder,
        private readonly array $supportedApiVersions,
        private readonly KnownIpsCollectorInterface $knownIpsCollector,
        private readonly HtmlSanitizer $htmlSanitizer,
        private readonly DefinitionInstanceRegistry $definitionInstanceRegistry,
        ParameterBagInterface $params,
        private readonly FilesystemOperator $fileSystem,
        private readonly string $refreshTokenTtl = 'P1W',
    ) {

    }

    #[Route(path: '/%cicada_administration.path_name%', name: 'administration.index', defaults: ['auth_required' => false], methods: ['GET'])]
    public function index(Request $request, Context $context): Response
    {
        $template = $this->finder->find('@Administration/administration/index.html.twig');
        $refreshTokenInterval = new \DateInterval($this->refreshTokenTtl);
        $refreshTokenTtl = $refreshTokenInterval->s + $refreshTokenInterval->i * 60 + $refreshTokenInterval->h * 3600 + $refreshTokenInterval->d * 86400;
        return $this->render($template, [
            'features' => Feature::getAll(),
            'systemLanguageId' => Defaults::LANGUAGE_SYSTEM,
            'defaultLanguageIds' => [Defaults::LANGUAGE_SYSTEM],
            'liveVersionId' => Defaults::LIVE_VERSION,
            'firstRunWizard' => $this->firstRunWizardService->frwShouldRun(),
            'apiVersion' => $this->getLatestApiVersion(),
            'cspNonce' => $request->attributes->get(PlatformRequest::ATTRIBUTE_CSP_NONCE),
            'refreshTokenTtl' => $refreshTokenTtl * 1000,
        ]);
    }

    #[Route(path: '/api/_admin/snippets', name: 'api.admin.snippets', methods: ['GET'])]
    public function snippets(Request $request): Response
    {
        $snippets = [];
        $locale = $request->query->get('locale', 'zh-CN');
        $snippets[$locale] = $this->snippetFinder->findSnippets((string) $locale);

        if ($locale !== 'zh-CN') {
            $snippets['zh-CN'] = $this->snippetFinder->findSnippets('zh-CN');
        }

        return new JsonResponse($snippets);
    }

    #[Route(path: '/api/_admin/known-ips', name: 'api.admin.known-ips', methods: ['GET'])]
    public function knownIps(Request $request): Response
    {
        $ips = [];

        foreach ($this->knownIpsCollector->collectIps($request) as $ip => $name) {
            $ips[] = [
                'name' => $name,
                'value' => $ip,
            ];
        }

        return new JsonResponse(['ips' => $ips]);
    }

    #[Route(path: '/%cicada_administration.path_name%/{pluginName}/index.html', name: 'administration.plugin.index', defaults: ['auth_required' => false], methods: ['GET'])]
    public function pluginIndex(string $pluginName): Response
    {
        try {
            $webpackIndexHtml = $this->fileSystem->read('bundles/' . $pluginName . '/administration/index.html');
            $publicAssetBaseUrl = $this->fileSystem->publicUrl('/');
        } catch (FilesystemException $e) {
            return new Response('Plugin index.html not found', Response::HTTP_NOT_FOUND);
        }

        $webpackIndexHtml = str_replace('__$ASSET_BASE_PATH$__', $publicAssetBaseUrl, $webpackIndexHtml);

        $response = new Response($webpackIndexHtml, Response::HTTP_OK, [
            'Content-Type' => 'text/html',
            'Content-Security-Policy' => 'script-src * \'unsafe-eval\' \'unsafe-inline\'',
            PlatformRequest::HEADER_FRAME_OPTIONS => 'sameorigin',
        ]);
        $response->setSharedMaxAge(3600);

        return $response;
    }

    #[Route(path: '/api/_admin/sanitize-html', name: 'api.admin.sanitize-html', methods: ['POST'])]
    public function sanitizeHtml(Request $request, Context $context): JsonResponse
    {
        if (!$request->request->has('html')) {
            throw RoutingException::missingRequestParameter('html');
        }

        $html = (string) $request->request->get('html');
        $field = (string) $request->request->get('field');

        if ($field === '') {
            return new JsonResponse(
                ['preview' => $this->htmlSanitizer->sanitize($html)]
            );
        }

        [$entityName, $propertyName] = explode('.', $field);
        $property = $this->definitionInstanceRegistry->getByEntityName($entityName)->getField($propertyName);

        if ($property === null) {
            throw RoutingException::invalidRequestParameter($field);
        }

        $flag = $property->getFlag(AllowHtml::class);

        if ($flag === null) {
            return new JsonResponse(
                ['preview' => strip_tags($html)]
            );
        }

        if ($flag instanceof AllowHtml && !$flag->isSanitized()) {
            return new JsonResponse(
                ['preview' => $html]
            );
        }

        return new JsonResponse(
            ['preview' => $this->htmlSanitizer->sanitize($html, [], false, $field)]
        );
    }
    private function getLatestApiVersion(): ?int
    {
        $sortedSupportedApiVersions = array_values($this->supportedApiVersions);

        usort($sortedSupportedApiVersions, fn (int $version1, int $version2) => \version_compare((string) $version1, (string) $version2));

        return array_pop($sortedSupportedApiVersions);
    }
}
