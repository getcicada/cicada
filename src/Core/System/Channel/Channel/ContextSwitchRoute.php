<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Channel;

use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Validation\EntityExists;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\Framework\Validation\DataValidationDefinition;
use Cicada\Core\Framework\Validation\DataValidator;
use Cicada\Core\System\Channel\Context\ChannelContextPersister;
use Cicada\Core\System\Channel\Context\ChannelContextService;
use Cicada\Core\System\Channel\ContextTokenResponse;
use Cicada\Core\System\Channel\Event\ChannelContextSwitchEvent;
use Cicada\Core\System\Channel\Event\SwitchContextEvent;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('core')]
class ContextSwitchRoute extends AbstractContextSwitchRoute
{
    private const SHIPPING_METHOD_ID = ChannelContextService::SHIPPING_METHOD_ID;
    private const PAYMENT_METHOD_ID = ChannelContextService::PAYMENT_METHOD_ID;
    private const BILLING_ADDRESS_ID = ChannelContextService::BILLING_ADDRESS_ID;
    private const SHIPPING_ADDRESS_ID = ChannelContextService::SHIPPING_ADDRESS_ID;
    private const COUNTRY_ID = ChannelContextService::COUNTRY_ID;
    private const STATE_ID = ChannelContextService::COUNTRY_STATE_ID;
    private const CURRENCY_ID = ChannelContextService::CURRENCY_ID;
    private const LANGUAGE_ID = ChannelContextService::LANGUAGE_ID;

    /**
     * @internal
     */
    public function __construct(
        private readonly DataValidator $validator,
        private readonly ChannelContextPersister $contextPersister,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function getDecorated(): AbstractContextSwitchRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/context', name: 'store-api.switch-context', methods: ['PATCH'])]
    public function switchContext(RequestDataBag $data, ChannelContext $context): ContextTokenResponse
    {
        $definition = new DataValidationDefinition('context_switch');

        $parameters = $data->only(
            self::SHIPPING_METHOD_ID,
            self::PAYMENT_METHOD_ID,
            self::BILLING_ADDRESS_ID,
            self::SHIPPING_ADDRESS_ID,
            self::COUNTRY_ID,
            self::STATE_ID,
            self::CURRENCY_ID,
            self::LANGUAGE_ID
        );

        // pre validate to ensure correct data type. Existence of entities is checked later
        $definition
            ->add(self::LANGUAGE_ID, new Type('string'))
            ->add(self::CURRENCY_ID, new Type('string'))
            ->add(self::SHIPPING_METHOD_ID, new Type('string'))
            ->add(self::PAYMENT_METHOD_ID, new Type('string'))
            ->add(self::BILLING_ADDRESS_ID, new Type('string'))
            ->add(self::SHIPPING_ADDRESS_ID, new Type('string'))
            ->add(self::COUNTRY_ID, new Type('string'))
            ->add(self::STATE_ID, new Type('string'))
        ;

        $event = new SwitchContextEvent($data, $context, $definition, $parameters);
        $this->eventDispatcher->dispatch($event, SwitchContextEvent::CONSISTENT_CHECK);
        $parameters = $event->getParameters();

        $this->validator->validate($parameters, $definition);

        $addressCriteria = new Criteria();
        if ($context->getMember()) {
            $addressCriteria->addFilter(new EqualsFilter('member_address.memberId', $context->getMember()->getId()));
        } else {
            // do not allow to set address ids if the member is not logged in
            if (isset($parameters[self::SHIPPING_ADDRESS_ID])) {
                throw CartException::memberNotLoggedIn();
            }

            if (isset($parameters[self::BILLING_ADDRESS_ID])) {
                throw CartException::memberNotLoggedIn();
            }
        }

        $currencyCriteria = new Criteria();
        $currencyCriteria->addFilter(
            new EqualsFilter('currency.channels.id', $context->getChannel()->getId())
        );

        $languageCriteria = new Criteria();
        $languageCriteria->addFilter(
            new EqualsFilter('language.channels.id', $context->getChannel()->getId())
        );

        $paymentMethodCriteria = new Criteria();
        $paymentMethodCriteria->addFilter(
            new EqualsFilter('payment_method.channels.id', $context->getChannel()->getId())
        );

        $shippingMethodCriteria = new Criteria();
        $shippingMethodCriteria->addFilter(
            new EqualsFilter('shipping_method.channels.id', $context->getChannel()->getId())
        );

        $definition
            ->add(self::LANGUAGE_ID, new EntityExists(['entity' => 'language', 'context' => $context->getContext(), 'criteria' => $languageCriteria]))
            ->add(self::CURRENCY_ID, new EntityExists(['entity' => 'currency', 'context' => $context->getContext(), 'criteria' => $currencyCriteria]))
            ->add(self::SHIPPING_METHOD_ID, new EntityExists(['entity' => 'shipping_method', 'context' => $context->getContext(), 'criteria' => $shippingMethodCriteria]))
            ->add(self::PAYMENT_METHOD_ID, new EntityExists(['entity' => 'payment_method', 'context' => $context->getContext(), 'criteria' => $paymentMethodCriteria]))
            ->add(self::BILLING_ADDRESS_ID, new EntityExists(['entity' => 'member_address', 'context' => $context->getContext(), 'criteria' => $addressCriteria]))
            ->add(self::SHIPPING_ADDRESS_ID, new EntityExists(['entity' => 'member_address', 'context' => $context->getContext(), 'criteria' => $addressCriteria]))
            ->add(self::COUNTRY_ID, new EntityExists(['entity' => 'country', 'context' => $context->getContext()]))
            ->add(self::STATE_ID, new EntityExists(['entity' => 'country_state', 'context' => $context->getContext()]))
        ;

        $event = new SwitchContextEvent($data, $context, $definition, $parameters);
        $this->eventDispatcher->dispatch($event, SwitchContextEvent::DATABASE_CHECK);
        $parameters = $event->getParameters();

        $this->validator->validate($parameters, $definition);

        $member = $context->getMember();
        $this->contextPersister->save(
            $context->getToken(),
            $parameters,
            $context->getChannel()->getId(),
            $member && empty($context->getPermissions()) ? $member->getId() : null
        );

        // Language was switched - Check new Domain
        $changeUrl = $this->checkNewDomain($parameters, $context);

        $event = new ChannelContextSwitchEvent($context, $data);
        $this->eventDispatcher->dispatch($event);

        return new ContextTokenResponse($context->getToken(), $changeUrl);
    }

    /**
     * @param array<mixed> $parameters
     */
    private function checkNewDomain(array $parameters, ChannelContext $context): ?string
    {
        if (
            !isset($parameters[self::LANGUAGE_ID])
            || $parameters[self::LANGUAGE_ID] === $context->getLanguageId()
        ) {
            return null;
        }

        $domains = $context->getChannel()->getDomains();
        if ($domains === null) {
            return null;
        }

        $langDomain = $domains->filterByProperty('languageId', $parameters[self::LANGUAGE_ID])->first();
        if ($langDomain === null) {
            return null;
        }

        return $langDomain->getUrl();
    }
}
