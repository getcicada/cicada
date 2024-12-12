<?php declare(strict_types=1);

namespace Cicada\Core\Content\Seo\Api;

use Cicada\Core\Content\Seo\ConfiguredSeoUrlRoute;
use Cicada\Core\Content\Seo\Exception\NoEntitiesForPreviewException;
use Cicada\Core\Content\Seo\SeoException;
use Cicada\Core\Content\Seo\SeoUrl\SeoUrlEntity;
use Cicada\Core\Content\Seo\SeoUrlGenerator;
use Cicada\Core\Content\Seo\SeoUrlPersister;
use Cicada\Core\Content\Seo\SeoUrlRoute\SeoUrlRouteConfig;
use Cicada\Core\Content\Seo\SeoUrlRoute\SeoUrlRouteRegistry;
use Cicada\Core\Content\Seo\Validation\SeoUrlDataValidationFactoryInterface;
use Cicada\Core\Content\Seo\Validation\SeoUrlValidationFactory;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\RequestCriteriaBuilder;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\Framework\Validation\DataValidator;
use Cicada\Core\System\Channel\ChannelCollection;
use Cicada\Core\System\Channel\ChannelEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
#[Package('content')]
class SeoActionController extends AbstractController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly SeoUrlGenerator $seoUrlGenerator,
        private readonly SeoUrlPersister $seoUrlPersister,
        private readonly DefinitionInstanceRegistry $definitionRegistry,
        private readonly SeoUrlRouteRegistry $seoUrlRouteRegistry,
        private readonly SeoUrlDataValidationFactoryInterface $seoUrlValidator,
        private readonly DataValidator $validator,
        private readonly EntityRepository $channelRepository,
        private readonly RequestCriteriaBuilder $requestCriteriaBuilder,
        private readonly DefinitionInstanceRegistry $definitionInstanceRegistry
    ) {
    }

    #[Route(path: '/api/_action/seo-url-template/validate', name: 'api.seo-url-template.validate', methods: ['POST'])]
    public function validate(Request $request, Context $context): JsonResponse
    {
        $context->setConsiderInheritance(true);

        $this->validateSeoUrlTemplate($request);
        $seoUrlTemplate = $request->request->all();

        // just call it to validate the template
        $this->getPreview($seoUrlTemplate, $context);

        return new JsonResponse();
    }

    #[Route(path: '/api/_action/seo-url-template/preview', name: 'api.seo-url-template.preview', methods: ['POST'])]
    public function preview(Request $request, Context $context): Response
    {
        $this->validateSeoUrlTemplate($request);
        $seoUrlTemplate = $request->request->all();

        $previewCriteria = new Criteria();
        if (\array_key_exists('criteria', $seoUrlTemplate) && \is_string($seoUrlTemplate['entityName']) && \is_array($seoUrlTemplate['criteria'])) {
            $definition = $this->definitionInstanceRegistry->getByEntityName($seoUrlTemplate['entityName']);

            $previewCriteria = $this->requestCriteriaBuilder->handleRequest(
                Request::create('', 'POST', $seoUrlTemplate['criteria']),
                $previewCriteria,
                $definition,
                $context
            );
            unset($seoUrlTemplate['criteria']);
        }

        try {
            $preview = $this->getPreview($seoUrlTemplate, $context, $previewCriteria);
        } catch (NoEntitiesForPreviewException) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse($preview);
    }

    #[Route(path: '/api/_action/seo-url-template/context', name: 'api.seo-url-template.context', methods: ['POST'])]
    public function getSeoUrlContext(RequestDataBag $data, Context $context): JsonResponse
    {
        $routeName = $data->get('routeName');
        $fk = $data->get('foreignKey');
        $seoUrlRoute = $this->seoUrlRouteRegistry->findByRouteName($routeName);
        if (!$seoUrlRoute) {
            throw SeoException::seoUrlRouteNotFound($routeName);
        }

        $config = $seoUrlRoute->getConfig();
        $repository = $this->getRepository($config);

        $criteria = new Criteria();
        if (!empty($fk)) {
            $criteria = new Criteria([$fk]);
        }
        $criteria->setLimit(1);

        $entity = $repository
            ->search($criteria, $context)
            ->first();

        if (!$entity) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $mapping = $seoUrlRoute->getMapping($entity, null);

        return new JsonResponse($mapping->getSeoPathInfoContext());
    }

    #[Route(path: '/api/_action/seo-url/canonical', name: 'api.seo-url.canonical', methods: ['PATCH'])]
    public function updateCanonicalUrl(RequestDataBag $seoUrl, Context $context): Response
    {
        if (!$seoUrl->has('routeName')) {
            throw SeoException::routeNameParameterIsMissing();
        }

        $seoUrlRoute = $this->seoUrlRouteRegistry->findByRouteName($seoUrl->get('routeName') ?? '');
        if (!$seoUrlRoute) {
            throw SeoException::seoUrlRouteNotFound($seoUrl->get('routeName'));
        }

        $validation = $this->seoUrlValidator->buildValidation($context, $seoUrlRoute->getConfig());

        $seoUrlData = $seoUrl->all();
        $this->validator->validate($seoUrlData, $validation);
        $seoUrlData['isModified'] ??= true;

        $channelId = $seoUrlData['channelId'] ?? null;

        if ($channelId === null) {
            throw SeoException::channelIdParameterIsMissing();
        }

        /** @var ChannelEntity|null $channel */
        $channel = $this->channelRepository->search(new Criteria([$channelId]), $context)->first();

        if ($channel === null) {
            throw SeoException::channelNotFound($channelId);
        }

        if ($channel->getTypeId() === Defaults::CHANNEL_TYPE_API) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $this->seoUrlPersister->updateSeoUrls(
            $context,
            $seoUrlData['routeName'],
            [$seoUrlData['foreignKey']],
            [$seoUrlData],
            $channel
        );

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    #[Route(path: '/api/_action/seo-url/create-custom-url', name: 'api.seo-url.create', methods: ['POST'])]
    public function createCustomSeoUrls(RequestDataBag $dataBag, Context $context): Response
    {
        /** @var ParameterBag $dataBag */
        $dataBag = $dataBag->get('urls');
        $urls = $dataBag->all();

        /** @var SeoUrlValidationFactory $validatorBuilder */
        $validatorBuilder = $this->seoUrlValidator;

        $validation = $validatorBuilder->buildValidation($context, null);
        $channels = new ChannelCollection();

        $channelIds = array_column($urls, 'channelId');

        if (!empty($channelIds)) {
            $channels = $this->channelRepository->search(new Criteria($channelIds), $context)->getEntities();
        }

        $writeData = [];

        foreach ($urls as $seoUrlData) {
            $id = $seoUrlData['channelId'] ?? null;

            $this->validator->validate($seoUrlData, $validation);
            $seoUrlData['isModified'] ??= true;

            $writeData[$id][] = $seoUrlData;
        }

        foreach ($writeData as $channelId => $writeRows) {
            if ($channelId === '') {
                throw SeoException::channelIdParameterIsMissing();
            }

            /** @var ChannelEntity $channelEntity */
            $channelEntity = $channels->get($channelId);

            if ($channelEntity === null) {
                throw SeoException::channelNotFound((string) $channelId);
            }

            if ($channelEntity->getTypeId() === Defaults::CHANNEL_TYPE_API) {
                continue;
            }

            $this->seoUrlPersister->updateSeoUrls(
                $context,
                $writeRows[0]['routeName'],
                array_column($writeRows, 'foreignKey'),
                $writeRows,
                $channelEntity
            );
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    #[Route(path: '/api/_action/seo-url-template/default/{routeName}', name: 'api.seo-url-template.default', methods: ['GET'])]
    public function getDefaultSeoTemplate(string $routeName, Context $context): JsonResponse
    {
        $seoUrlRoute = $this->seoUrlRouteRegistry->findByRouteName($routeName);

        if (!$seoUrlRoute) {
            throw SeoException::seoUrlRouteNotFound($routeName);
        }

        return new JsonResponse(['defaultTemplate' => $seoUrlRoute->getConfig()->getTemplate()]);
    }

    private function validateSeoUrlTemplate(Request $request): void
    {
        if (!$request->request->has('template')) {
            throw SeoException::templateParameterIsMissing();
        }

        if (!$request->request->has('channelId')) {
            throw SeoException::channelIdParameterIsMissing();
        }

        if (!$request->request->has('routeName')) {
            throw SeoException::routeNameParameterIsMissing();
        }

        if (!$request->request->has('entityName')) {
            throw SeoException::entityNameParameterIsMissing();
        }
    }

    /**
     * @param array<string, mixed> $seoUrlTemplate
     *
     * @return array<SeoUrlEntity>
     */
    private function getPreview(array $seoUrlTemplate, Context $context, ?Criteria $previewCriteria = null): array
    {
        $seoUrlRoute = $this->seoUrlRouteRegistry->findByRouteName($seoUrlTemplate['routeName']);

        if (!$seoUrlRoute) {
            throw SeoException::seoUrlRouteNotFound($seoUrlTemplate['routeName']);
        }

        $config = $seoUrlRoute->getConfig();
        $config->setSkipInvalid(false);
        $repository = $this->getRepository($config);

        $criteria = new Criteria();
        if ($previewCriteria !== null) {
            $criteria = $previewCriteria;
        }
        $criteria->setLimit(10);

        $ids = $repository->searchIds($criteria, $context)->getIds();

        if (empty($ids)) {
            throw SeoException::noEntitiesForPreview($repository->getDefinition()->getEntityName(), $seoUrlTemplate['routeName']);
        }

        $channelId = $seoUrlTemplate['channelId'] ?? null;
        $template = $seoUrlTemplate['template'] ?? '';

        if (\is_string($channelId)) {
            /** @var ChannelEntity|null $channel */
            $channel = $this->channelRepository->search((new Criteria([$channelId]))->setLimit(1), $context)->get($channelId);

            if ($channel === null) {
                throw SeoException::invalidChannelId($channelId);
            }
        } else {
            /** @var ChannelEntity|null $channel */
            $channel = $this->channelRepository
                ->search(
                    (new Criteria())->addFilter(new EqualsFilter('typeId', Defaults::SALES_CHANNEL_TYPE_STOREFRONT))->setLimit(1),
                    $context
                )
                ->first();
        }

        if ($channel === null) {
            throw SeoException::channelIdParameterIsMissing();
        }

        $result = $this->seoUrlGenerator->generate($ids, $template, new ConfiguredSeoUrlRoute($seoUrlRoute, $config), $context, $channel);
        if (\is_array($result)) {
            return $result;
        }

        return iterator_to_array($result);
    }

    private function getRepository(SeoUrlRouteConfig $config): EntityRepository
    {
        return $this->definitionRegistry->getRepository($config->getDefinition()->getEntityName());
    }
}
