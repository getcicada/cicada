<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\Commands;

use Cicada\Core\Content\Sitemap\Event\SitemapChannelCriteriaEvent;
use Cicada\Core\Content\Sitemap\Exception\AlreadyLockedException;
use Cicada\Core\Content\Sitemap\Service\SitemapExporterInterface;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\Aggregate\ChannelDomain\ChannelDomainEntity;
use Cicada\Core\System\Channel\Context\AbstractChannelContextFactory;
use Cicada\Core\System\Channel\Context\ChannelContextService;
use Cicada\Core\System\Channel\ChannelContext;
use Cicada\Core\System\Channel\ChannelEntity;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'sitemap:generate',
    description: 'Generates sitemap files',
)]
#[Package('services-settings')]
class SitemapGenerateCommand extends Command
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $channelRepository,
        private readonly SitemapExporterInterface $sitemapExporter,
        private readonly AbstractChannelContextFactory $channelContextFactory,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->addOption('channelId', 'i', InputOption::VALUE_OPTIONAL, 'Generate sitemap only for for this sales channel')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force generation, even if generation has been locked by some other process'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $channelId = $input->getOption('channelId');

        $context = Context::createCLIContext();

        $criteria = $this->createCriteria($channelId);

        $this->eventDispatcher->dispatch(
            new SitemapChannelCriteriaEvent($criteria, $context)
        );

        $channels = $this->channelRepository->search($criteria, $context);

        /** @var ChannelEntity $channel */
        foreach ($channels as $channel) {
            /** @var list<string> $languageIds */
            $languageIds = $channel->getDomains()->map(fn (ChannelDomainEntity $channelDomain) => $channelDomain->getLanguageId());

            $languageIds = array_unique($languageIds);

            foreach ($languageIds as $languageId) {
                $channelContext = $this->channelContextFactory->create('', $channel->getId(), [ChannelContextService::LANGUAGE_ID => $languageId]);
                $output->writeln(\sprintf('Generating sitemaps for sales channel %s (%s) with and language %s...', $channel->getId(), $channel->getName(), $languageId));

                try {
                    $this->generateSitemap($channelContext, $input->getOption('force'));
                } catch (AlreadyLockedException $exception) {
                    $output->writeln(\sprintf('ERROR: %s', $exception->getMessage()));
                }
            }
        }

        $output->writeln('done!');

        return self::SUCCESS;
    }

    private function generateSitemap(ChannelContext $channelContext, bool $force, ?string $lastProvider = null, ?int $offset = null): void
    {
        $result = $this->sitemapExporter->generate($channelContext, $force, $lastProvider, $offset);
        if ($result->isFinish() === false) {
            $this->generateSitemap($channelContext, $force, $result->getProvider(), $result->getOffset());
        }
    }

    private function createCriteria(?string $channelId = null): Criteria
    {
        $criteria = $channelId ? new Criteria([$channelId]) : new Criteria();
        $criteria->addAssociation('domains');
        $criteria->addFilter(new NotFilter(
            NotFilter::CONNECTION_AND,
            [new EqualsFilter('domains.id', null)]
        ));

        $criteria->addAssociation('type');
        $criteria->addFilter(new EqualsFilter('type.id', Defaults::SALES_CHANNEL_TYPE_STOREFRONT));

        return $criteria;
    }
}
