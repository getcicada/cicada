<?php declare(strict_types=1);

namespace Cicada\Administration\System\Channel\Subscriber;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\User\Aggregate\UserConfig\UserConfigCollection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('administration')]
class ChannelUserConfigSubscriber implements EventSubscriberInterface
{
    final public const CONFIG_KEY = 'sales-channel-favorites';

    /**
     * @internal
     */
    public function __construct(private readonly EntityRepository $userConfigRepository)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'channel.deleted' => 'onChannelDeleted',
        ];
    }

    public function onChannelDeleted(EntityDeletedEvent $deletedEvent): void
    {
        $context = $deletedEvent->getContext();

        $deletedChannelIds = $deletedEvent->getIds();

        $writeUserConfigs = [];
        foreach ($this->getAllFavoriteUserConfigs($context) as $userConfigEntity) {
            $channelIds = $userConfigEntity->getValue();

            if ($channelIds === null) {
                continue;
            }

            // Find matching IDs
            $matchingIds = array_intersect($deletedChannelIds, $channelIds);

            if (!$matchingIds) {
                continue;
            }

            // Removes the IDs from $matchingIds from the array
            $newUserConfigArray = array_diff($channelIds, $matchingIds);
            $writeUserConfigs[] = [
                'id' => $userConfigEntity->getId(),
                'value' => array_values($newUserConfigArray),
            ];
        }

        $this->userConfigRepository->upsert($writeUserConfigs, $context);
    }

    private function getAllFavoriteUserConfigs(Context $context): UserConfigCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('key', self::CONFIG_KEY));

        /** @var UserConfigCollection $result */
        $result = $this->userConfigRepository->search($criteria, $context)->getEntities();

        return $result;
    }
}
