<?php declare(strict_types=1);

namespace Cicada\Core\System\SystemConfig\Api;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\System\SystemConfig\Service\ConfigurationService;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\System\SystemConfig\Validation\SystemConfigValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
#[Package('services-settings')]
class SystemConfigController extends AbstractController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ConfigurationService $configurationService,
        private readonly SystemConfigService $systemConfig,
        private readonly SystemConfigValidator $systemConfigValidator
    ) {
    }

    #[Route(path: '/api/_action/system-config/check', name: 'api.action.core.system-config.check', defaults: ['_acl' => ['system_config:read']], methods: ['GET'])]
    public function checkConfiguration(Request $request, Context $context): JsonResponse
    {
        $domain = (string) $request->query->get('domain');

        if ($domain === '') {
            return new JsonResponse(false);
        }

        return new JsonResponse($this->configurationService->checkConfiguration($domain, $context));
    }

    #[Route(path: '/api/_action/system-config/schema', name: 'api.action.core.system-config', methods: ['GET'])]
    public function getConfiguration(Request $request, Context $context): JsonResponse
    {
        $domain = (string) $request->query->get('domain');

        if ($domain === '') {
            throw RoutingException::missingRequestParameter('domain');
        }

        return new JsonResponse($this->configurationService->getConfiguration($domain, $context));
    }

    #[Route(path: '/api/_action/system-config', name: 'api.action.core.system-config.value', defaults: ['_acl' => ['system_config:read']], methods: ['GET'])]
    public function getConfigurationValues(Request $request): JsonResponse
    {
        $domain = (string) $request->query->get('domain');
        if ($domain === '') {
            throw RoutingException::missingRequestParameter('domain');
        }

        $channelId = $request->query->get('channelId');
        if (!\is_string($channelId)) {
            $channelId = null;
        }

        $inherit = $request->query->getBoolean('inherit');

        $values = $this->systemConfig->getDomain($domain, $channelId, $inherit);
        if (empty($values)) {
            $json = '{}';
        } else {
            $json = json_encode($values, \JSON_PRESERVE_ZERO_FRACTION);
        }

        return new JsonResponse($json, 200, [], true);
    }

    #[Route(path: '/api/_action/system-config', name: 'api.action.core.save.system-config', defaults: ['_acl' => ['system_config:update', 'system_config:create', 'system_config:delete']], methods: ['POST'])]
    public function saveConfiguration(Request $request): JsonResponse
    {
        $channelId = $request->query->get('channelId');
        if (!\is_string($channelId)) {
            $channelId = null;
        }

        $kvs = $request->request->all();
        $this->systemConfig->setMultiple($kvs, $channelId);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route(path: '/api/_action/system-config/batch', name: 'api.action.core.save.system-config.batch', defaults: ['_acl' => ['system_config:update', 'system_config:create', 'system_config:delete']], methods: ['POST'])]
    public function batchSaveConfiguration(Request $request, Context $context): JsonResponse
    {
        $this->systemConfigValidator->validate($request->request->all(), $context);

        /**
         * @var string $channelId
         * @var array<string, mixed> $kvs
         */
        foreach ($request->request->all() as $channelId => $kvs) {
            if ($channelId === 'null') {
                $channelId = null;
            }

            $this->systemConfig->setMultiple($kvs, $channelId);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}