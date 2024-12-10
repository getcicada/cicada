<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Subscriber;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\DeleteCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Channel\Aggregate\ChannelType\ChannelTypeDefinition;
use Cicada\Core\System\Channel\Exception\DefaultChannelTypeCannotBeDeleted;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('frontend')]
class ChannelTypeValidator implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PreWriteValidationEvent::class => 'preWriteValidateEvent',
        ];
    }

    public function preWriteValidateEvent(PreWriteValidationEvent $event): void
    {
        foreach ($event->getCommands() as $command) {
            if (!$command instanceof DeleteCommand || $command->getEntityName() !== ChannelTypeDefinition::ENTITY_NAME) {
                continue;
            }

            $id = Uuid::fromBytesToHex($command->getPrimaryKey()['id']);

            if (\in_array($id, [Defaults::CHANNEL_TYPE_API, Defaults::CHANNEL_TYPE_WEB, Defaults::SALES_CHANNEL_TYPE_PRODUCT_COMPARISON], true)) {
                $event->getExceptions()->add(new DefaultChannelTypeCannotBeDeleted($id));
            }
        }
    }
}
