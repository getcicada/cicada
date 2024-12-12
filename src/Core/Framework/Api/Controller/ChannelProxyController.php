<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\Controller;

use Cicada\Frontend\Member\ImitateMemberTokenGenerator;
use Cicada\Core\Framework\Api\ApiException;
use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Api\Exception\InvalidChannelIdException;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Validation\EntityExists;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Util\Random;
use Cicada\Core\Framework\Validation\BuildValidationEvent;
use Cicada\Core\Framework\Validation\Constraint\Uuid;
use Cicada\Core\Framework\Validation\DataBag\DataBag;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\Framework\Validation\DataValidationDefinition;
use Cicada\Core\Framework\Validation\DataValidator;
use Cicada\Core\Framework\Validation\Exception\ConstraintViolationException;
use Cicada\Core\PlatformRequest;
use Cicada\Core\ChannelRequest;
use Cicada\Core\System\Channel\Context\ChannelContextPersister;
use Cicada\Core\System\Channel\Context\ChannelContextService;
use Cicada\Core\System\Channel\Context\ChannelContextServiceInterface;
use Cicada\Core\System\Channel\Context\ChannelContextServiceParameters;
use Cicada\Core\System\Channel\Event\ChannelContextSwitchEvent;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Core\System\Channel\ChannelEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

#[Route(defaults: ['_routeScope' => ['api']])]
#[Package('core')]
class ChannelProxyController extends AbstractController
{

    private const SALES_CHANNEL_ID = 'channelId';

    protected Processor $processor;

    /**
     * @internal
     */
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly EntityRepository $channelRepository,
        protected DataValidator $validator,
        protected ChannelContextPersister $contextPersister,
        private readonly ChannelContextServiceInterface $contextService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[Route(path: '/api/_proxy/store-api/{channelId}/{_path}', name: 'api.proxy.store-api', requirements: ['_path' => '.*'])]
    public function proxy(string $_path, string $channelId, Request $request, Context $context): Response
    {
        $channel = $this->fetchChannel($channelId, $context);

        $channelApiRequest = $this->setUpChannelApiRequest($_path, $channelId, $request, $channel, $context);

        return $this->wrapInChannelApiRoute($channelApiRequest, fn (): Response => $this->kernel->handle($channelApiRequest, HttpKernelInterface::SUB_REQUEST));
    }
    
    #[Route(path: '/api/_proxy/switch-member', name: 'api.proxy.switch-member', defaults: ['_acl' => ['api_proxy_switch-member']], methods: ['PATCH'])]
    public function assignMember(Request $request, Context $context): Response
    {
        if (!$request->request->has(self::SALES_CHANNEL_ID)) {
            throw ApiException::channelIdParameterIsMissing();
        }

        $channelId = (string) $request->request->get('channelId');

        if (!$request->request->has(self::Member_ID)) {
            throw ApiException::channelIdParameterIsMissing();
        }

        $this->fetchChannel($channelId, $context);

        $channelContext = $this->fetchChannelContext($channelId, $request, $context);

        $this->persistPermissions($request, $channelContext);

        $this->updateMemberToContext($request->get(self::Member_ID), $channelContext);

        $content = json_encode([
            PlatformRequest::HEADER_CONTEXT_TOKEN => $channelContext->getToken(),
        ], \JSON_THROW_ON_ERROR);
        $response = new Response();
        $response->headers->set('content-type', 'application/json');
        $response->setContent($content ?: null);

        return $response;
    }

    /**
     * @param callable(): Response $call
     */
    private function wrapInChannelApiRoute(Request $request, callable $call): Response
    {
        $requestStackBackup = $this->clearRequestStackWithBackup($this->requestStack);
        $this->requestStack->push($request);

        try {
            return $call();
        } finally {
            $this->restoreRequestStack($this->requestStack, $requestStackBackup);
        }
    }

    private function setUpChannelApiRequest(string $path, string $channelId, Request $request, ChannelEntity $channel, Context $context): Request
    {
        $contextToken = $this->getContextToken($request);

        $server = array_merge($request->server->all(), ['REQUEST_URI' => '/store-api/' . $path]);
        $subrequest = $request->duplicate(null, null, [], null, null, $server);

        $subrequest->headers->set(PlatformRequest::HEADER_ACCESS_KEY, $channel->getAccessKey());
        $subrequest->headers->set(PlatformRequest::HEADER_CONTEXT_TOKEN, $contextToken);
        $subrequest->attributes->set(PlatformRequest::ATTRIBUTE_OAUTH_CLIENT_ID, $channel->getAccessKey());

        $channelContext = $this->fetchChannelContext($channelId, $subrequest, $context);

        $subrequest->attributes->set(PlatformRequest::ATTRIBUTE_CHANNEL_CONTEXT_OBJECT, $channelContext);
        $subrequest->attributes->set(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT, $channelContext->getContext());

        return $subrequest;
    }

    /**
     * @throws InconsistentCriteriaIdsException
     * @throws InvalidChannelIdException
     */
    private function fetchChannel(string $channelId, Context $context): ChannelEntity
    {
        /** @var ChannelEntity|null $channel */
        $channel = $this->channelRepository->search(new Criteria([$channelId]), $context)->get($channelId);

        if ($channel === null) {
            throw ApiException::invalidChannelId($channelId);
        }

        return $channel;
    }

    /**
     * @throws ConstraintViolationException
     */
    private function validateImitateMemberDataFields(DataBag $data, Context $context): void
    {
        $definition = new DataValidationDefinition('impersonation.generate-token');

        $definition
            ->add(self::SALES_CHANNEL_ID, new Uuid(), new EntityExists(['entity' => 'channel', 'context' => $context]))
            ->add(self::Member_ID, new Uuid(), new EntityExists(['entity' => 'member', 'context' => $context]));

        $validationEvent = new BuildValidationEvent($definition, $data, $context);
        $this->eventDispatcher->dispatch($validationEvent, $validationEvent->getName());

        $this->validator->validate($data->all(), $definition);
    }

    private function getContextToken(Request $request): string
    {
        $contextToken = $request->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN);

        if ($contextToken === null) {
            $contextToken = Random::getAlphanumericString(32);
        }

        return $contextToken;
    }

    /**
     * @return array<Request>
     */
    private function clearRequestStackWithBackup(RequestStack $requestStack): array
    {
        $requestStackBackup = [];

        while ($requestStack->getMainRequest()) {
            $request = $requestStack->pop();

            if ($request === null) {
                continue;
            }

            $requestStackBackup[] = $request;
        }

        return $requestStackBackup;
    }

    /**
     * @param array<Request> $requestStackBackup
     */
    private function restoreRequestStack(RequestStack $requestStack, array $requestStackBackup): void
    {
        $this->clearRequestStackWithBackup($requestStack);

        foreach ($requestStackBackup as $backedUpRequest) {
            $requestStack->push($backedUpRequest);
        }
    }

    private function fetchChannelContext(string $channelId, Request $request, Context $originalContext): ChannelContext
    {
        $contextToken = $this->getContextToken($request);

        return $this->contextService->get(
            new ChannelContextServiceParameters(
                $channelId,
                $contextToken,
                $request->headers->get(PlatformRequest::HEADER_LANGUAGE_ID),
                null,
                $originalContext
            )
        );
    }

    private function updateMemberToContext(string $memberId, ChannelContext $context): void
    {
        $data = new DataBag();
        $data->set(self::Member_ID, $memberId);

        $definition = new DataValidationDefinition('context_switch');
        $parameters = $data->only(
            self::Member_ID
        );

        $memberCriteria = new Criteria();
        $memberCriteria->addFilter(new EqualsFilter('member.id', $parameters[self::Member_ID]));

        $definition
            ->add(self::Member_ID, new EntityExists(['entity' => 'member', 'context' => $context->getContext(), 'criteria' => $memberCriteria]))
        ;

        $this->validator->validate($parameters, $definition);

        $isSwitchNewMember = true;
        if ($context->getMember()) {
            // Check if member switch to another member or not
            $isSwitchNewMember = $context->getMember()->getId() !== $parameters[self::Member_ID];
        }

        if (!$isSwitchNewMember) {
            return;
        }

        $this->contextPersister->save(
            $context->getToken(),
            [
                'memberId' => $parameters[self::Member_ID],
                'billingAddressId' => null,
                'shippingAddressId' => null,
                'shippingMethodId' => null,
                'paymentMethodId' => null,
                'languageId' => null,
                'currencyId' => null,
            ],
            $context->getChannel()->getId()
        );
        $event = new ChannelContextSwitchEvent($context, $data);
        $this->eventDispatcher->dispatch($event);
    }

    private function persistPermissions(Request $request, ChannelContext $channelContext): void
    {
        $contextToken = $channelContext->getToken();

        $channelId = $channelContext->getChannelId();

        $payload = $this->contextPersister->load($contextToken, $channelId);
        $requestPermissions = $request->get(ChannelContextService::PERMISSIONS);

        if (\in_array(ChannelContextService::PERMISSIONS, $payload, true) && !$requestPermissions) {
            return;
        }

        $this->contextPersister->save($contextToken, $payload, $channelId);
    }
    private function validateShippingCostsParameters(Request $request): void
    {
        if (!$request->request->has('shippingCosts')) {
            throw ApiException::shippingCostsParameterIsMissing();
        }

        $validation = new DataValidationDefinition('shipping-cost');
        $validation->add('unitPrice', new NotBlank(), new Type('numeric'), new GreaterThanOrEqual(['value' => 0]));
        $validation->add('totalPrice', new NotBlank(), new Type('numeric'), new GreaterThanOrEqual(['value' => 0]));
        $this->validator->validate($request->request->all('shippingCosts'), $validation);
    }
}
