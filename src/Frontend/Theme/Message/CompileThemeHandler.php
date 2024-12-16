<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme\Message;

use Cicada\Administration\Notification\NotificationService;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Channel\ChannelEntity;
use Cicada\Frontend\Theme\ConfigLoader\AbstractConfigLoader;
use Cicada\Frontend\Theme\Exception\ThemeException;
use Cicada\Frontend\Theme\FrontendPluginRegistry;
use Cicada\Frontend\Theme\ThemeCompilerInterface;
use Cicada\Frontend\Theme\ThemeService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
#[Package('frontend')]
final class CompileThemeHandler
{
    public function __construct(
        private readonly ThemeCompilerInterface $themeCompiler,
        private readonly AbstractConfigLoader $configLoader,
        private readonly FrontendPluginRegistry $extensionRegistry,
        private readonly NotificationService $notificationService,
        private readonly EntityRepository $channelRepository
    ) {
    }

    public function __invoke(CompileThemeMessage $message): void
    {
        $message->getContext()->addState(ThemeService::STATE_NO_QUEUE);
        $this->themeCompiler->compileTheme(
            $message->getChannelId(),
            $message->getThemeId(),
            $this->configLoader->load($message->getThemeId(), $message->getContext()),
            $this->extensionRegistry->getConfigurations(),
            $message->isWithAssets(),
            $message->getContext()
        );

        if ($message->getContext()->getScope() !== Context::USER_SCOPE) {
            return;
        }
        /** @var ChannelEntity|null $channel */
        $channel = $this->channelRepository->search(
            new Criteria([$message->getChannelId()]),
            $message->getContext()
        )->first();

        if ($channel === null) {
            throw ThemeException::channelNotFound($message->getChannelId());
        }

        $this->notificationService->createNotification(
            [
                'id' => Uuid::randomHex(),
                'status' => 'info',
                'message' => 'Compilation for sales channel ' . $channel->getName() . ' completed',
                'requiredPrivileges' => [],
            ],
            $message->getContext()
        );
    }
}
