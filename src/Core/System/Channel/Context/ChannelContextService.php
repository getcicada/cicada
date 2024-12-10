<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Context;

use Cicada\Core\Checkout\Cart\CartRuleLoader;
use Cicada\Core\Checkout\Cart\Channel\CartService;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Util\Random;
use Cicada\Core\Profiling\Profiler;
use Cicada\Core\System\Channel\Event\ChannelContextCreatedEvent;
use Cicada\Core\System\Channel\ChannelContext;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[Package('core')]
class ChannelContextService implements ChannelContextServiceInterface
{
    final public const LANGUAGE_ID = 'languageId';

    final public const MEMBER_ID = 'memberId';

    final public const MEMBER_GROUP_ID = 'memberGroupId';

    final public const VERSION_ID = 'version-id';

    final public const PERMISSIONS = 'permissions';

    final public const DOMAIN_ID = 'domainId';

    final public const ORIGINAL_CONTEXT = 'originalContext';

    final public const IMITATING_USER_ID = 'imitatingUserId';

    /**
     * @internal
     */
    public function __construct(
        private readonly AbstractChannelContextFactory $factory,
        private readonly ChannelContextPersister $contextPersister,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function get(ChannelContextServiceParameters $parameters): ChannelContext
    {
        return Profiler::trace('sales-channel-context', function () use ($parameters) {
            $token = $parameters->getToken();

            $session = $this->contextPersister->load($token, $parameters->getChannelId());

            if ($session['expired'] ?? false) {
                $token = Random::getAlphanumericString(32);
            }

            if ($parameters->getLanguageId() !== null) {
                $session[self::LANGUAGE_ID] = $parameters->getLanguageId();
            }
            if ($parameters->getDomainId() !== null) {
                $session[self::DOMAIN_ID] = $parameters->getDomainId();
            }

            if ($parameters->getOriginalContext() !== null) {
                $session[self::ORIGINAL_CONTEXT] = $parameters->getOriginalContext();
            }

            if ($parameters->getMemberId() !== null) {
                $session[self::MEMBER_ID] = $parameters->getMemberId();
            }

            if ($parameters->getImitatingUserId() !== null) {
                $session[self::IMITATING_USER_ID] = $parameters->getImitatingUserId();
            }

            $context = $this->factory->create($token, $parameters->getChannelId(), $session);
            $this->eventDispatcher->dispatch(new ChannelContextCreatedEvent($context, $token, $session));

            return $context;
        });
    }
}
