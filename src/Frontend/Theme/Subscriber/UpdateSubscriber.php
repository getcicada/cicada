<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme\Subscriber;

use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\PluginLifecycleService;
use Cicada\Core\Framework\Update\Event\UpdatePostFinishEvent;
use Cicada\Core\System\Channel\ChannelEntity;
use Cicada\Frontend\Theme\Exception\ThemeCompileException;
use Cicada\Frontend\Theme\ThemeCollection;
use Cicada\Frontend\Theme\ThemeEntity;
use Cicada\Frontend\Theme\ThemeLifecycleService;
use Cicada\Frontend\Theme\ThemeService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('frontend')]
class UpdateSubscriber implements EventSubscriberInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ThemeService $themeService,
        private readonly ThemeLifecycleService $themeLifecycleService,
        private readonly EntityRepository $channelRepository
    ) {
    }

    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            UpdatePostFinishEvent::class => 'updateFinished',
        ];
    }

    /**
     * @internal
     */
    public function updateFinished(UpdatePostFinishEvent $event): void
    {
        $context = $event->getContext();

        if ($context->hasState(PluginLifecycleService::STATE_SKIP_ASSET_BUILDING)) {
            return;
        }

        $this->themeLifecycleService->refreshThemes($context);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->getAssociation('themes')
            ->addFilter(new EqualsFilter('active', true));

        $alreadyCompiled = [];
        /** @var ChannelEntity $channel */
        foreach ($this->channelRepository->search($criteria, $context) as $channel) {
            $themes = $channel->getExtension('themes');
            if (!$themes instanceof ThemeCollection) {
                continue;
            }

            $failedThemes = [];

            /** @var ThemeEntity $theme */
            foreach ($themes as $theme) {
                // NEXT-21735 - his is covered randomly
                // @codeCoverageIgnoreStart
                if (\in_array($theme->getId(), $alreadyCompiled, true) !== false) {
                    continue;
                }
                // @codeCoverageIgnoreEnd

                try {
                    $alreadyCompiled += $this->themeService->compileThemeById($theme->getId(), $context);
                } catch (ThemeCompileException $e) {
                    $failedThemes[] = $theme->getName();
                    $alreadyCompiled[] = $theme->getId();
                }
            }

            if (!empty($failedThemes)) {
                $event->appendPostUpdateMessage('Theme(s): ' . implode(', ', $failedThemes) . ' could not be recompiled.');
            }
        }
    }
}