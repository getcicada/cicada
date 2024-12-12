<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Context;


use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Core\System\Channel\Event\ChannelContextPermissionsChangedEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[Package('frontend')]
class ChannelContextFactory extends AbstractChannelContextFactory
{
    /**
     * @internal
     *
     */
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AbstractBaseContextFactory $baseContextFactory
    ) {
    }

    public function getDecorated(): AbstractChannelContextFactory
    {
        throw new DecorationPatternException(self::class);
    }

    public function create(string $token, string $channelId, array $options = []): ChannelContext
    {
        // we split the context generation to allow caching of the base context
        $base = $this->baseContextFactory->create($channelId, $options);

        $context = new Context(
            $base->getContext()->getSource(),
            $base->getContext()->getLanguageIdChain(),
            $base->getContext()->getVersionId(),
            true,
        );

        $channelContext = new ChannelContext(
            $context,
            $token,
            $options[ChannelContextService::DOMAIN_ID] ?? null,
            $base->getChannel(),
            []
        );

        if (\array_key_exists(ChannelContextService::PERMISSIONS, $options)) {
            $channelContext->setPermissions($options[ChannelContextService::PERMISSIONS]);

            $event = new ChannelContextPermissionsChangedEvent($channelContext, $options[ChannelContextService::PERMISSIONS]);
            $this->eventDispatcher->dispatch($event);

            $channelContext->lockPermissions();
        }

        if (\array_key_exists(ChannelContextService::IMITATING_USER_ID, $options)) {
            $channelContext->setImitatingUserId($options[ChannelContextService::IMITATING_USER_ID]);
        }

        return $channelContext;
    }
}
