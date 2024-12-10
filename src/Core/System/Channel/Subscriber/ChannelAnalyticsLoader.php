<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel\Subscriber;

use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\Aggregate\ChannelAnalytics\ChannelAnalyticsEntity;
use Cicada\Frontend\Event\FrontendRenderEvent;

/**
 * @internal
 */
#[Package('frontend')]
class ChannelAnalyticsLoader
{
    public function __construct(
        private readonly EntityRepository $channelAnalyticsRepository,
    ) {
    }

    public function loadAnalytics(FrontendRenderEvent $event): void
    {
        $channelContext = $event->getChannelContext();
        $channel = $channelContext->getChannel();
        $analyticsId = $channel->getAnalyticsId();

        if (empty($analyticsId)) {
            return;
        }

        $criteria = new Criteria([$analyticsId]);
        $criteria->setTitle('sales-channel::load-analytics');

        /** @var ChannelAnalyticsEntity|null $analytics */
        $analytics = $this->channelAnalyticsRepository->search($criteria, $channelContext->getContext())->first();

        $event->setParameter('frontendAnalytics', $analytics);
    }
}
