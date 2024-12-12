<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\ScheduledTask;

use Psr\Log\LoggerInterface;
use Cicada\Core\Content\Sitemap\Exception\AlreadyLockedException;
use Cicada\Core\Content\Sitemap\Service\SitemapExporterInterface;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\Context\AbstractChannelContextFactory;
use Cicada\Core\System\Channel\Context\ChannelContextService;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
#[Package('services-settings')]
final class SitemapMessageHandler
{
    /**
     * @internal
     */
    public function __construct(
        private readonly AbstractChannelContextFactory $channelContextFactory,
        private readonly SitemapExporterInterface $sitemapExporter,
        private readonly LoggerInterface $logger,
        private readonly SystemConfigService $systemConfigService,
    ) {
    }

    public function __invoke(SitemapMessage $message): void
    {
        $sitemapRefreshStrategy = $this->systemConfigService->getInt('core.sitemap.sitemapRefreshStrategy');
        if ($sitemapRefreshStrategy !== SitemapExporterInterface::STRATEGY_SCHEDULED_TASK) {
            return;
        }

        $this->generate($message);
    }

    private function generate(SitemapMessage $message): void
    {
        if ($message->getLastChannelId() === null || $message->getLastLanguageId() === null) {
            return;
        }

        $context = $this->channelContextFactory->create('', $message->getLastChannelId(), [ChannelContextService::LANGUAGE_ID => $message->getLastLanguageId()]);

        try {
            $this->sitemapExporter->generate($context, true, $message->getLastProvider(), $message->getNextOffset());
        } catch (AlreadyLockedException $exception) {
            $this->logger->error(\sprintf('ERROR: %s', $exception->getMessage()));
        }
    }
}
