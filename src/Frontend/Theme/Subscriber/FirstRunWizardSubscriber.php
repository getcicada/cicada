<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme\Subscriber;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Event\FirstRunWizardFinishedEvent;
use Cicada\Frontend\Theme\ThemeEntity;
use Cicada\Frontend\Theme\ThemeLifecycleService;
use Cicada\Frontend\Theme\ThemeService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('frontend')]
class FirstRunWizardSubscriber implements EventSubscriberInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ThemeService $themeService,
        private readonly ThemeLifecycleService $themeLifecycleService,
        private readonly EntityRepository $themeRepository,
        private readonly EntityRepository $themeChannelRepository,
        private readonly EntityRepository $channelRepository
    ) {
    }

    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FirstRunWizardFinishedEvent::class => 'frwFinished',
        ];
    }

    public function frwFinished(FirstRunWizardFinishedEvent $event): void
    {
        // only run on open -> completed|failed transition
        if (!$event->getPreviousState()->isOpen() || $event->getState()->isOpen()) {
            return;
        }

        $context = $event->getContext();

        $this->themeLifecycleService->refreshThemes($context);

        $themeCriteria = new Criteria();
        $themeCriteria->addAssociation('channels');
        $themeCriteria->addFilter(new EqualsFilter('technicalName', 'Frontend'));
        /** @var ThemeEntity|null $theme */
        $theme = $this->themeRepository->search($themeCriteria, $context)->first();
        if (!$theme) {
            throw new \RuntimeException('Default theme not found');
        }

        $themeChannels = $theme->getChannels();
        // only run if the themes are not already initialised
        if ($themeChannels && $themeChannels->count() > 0) {
            return;
        }

        $channelCriteria = new Criteria();
        $channelCriteria->addFilter(new EqualsFilter('typeId', Defaults::CHANNEL_TYPE_STOREFRONT));
        $channelIds = $this->channelRepository->search($channelCriteria, $context)->getIds();
        foreach ($channelIds as $id) {
            $this->themeService->compileTheme($id, $theme->getId(), $context);
            $this->themeChannelRepository->upsert([[
                'themeId' => $theme->getId(),
                'channelId' => $id,
            ]], $context);
        }
    }
}
