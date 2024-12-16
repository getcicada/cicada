<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme\Command;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Channel\ChannelCollection;
use Cicada\Core\System\Channel\ChannelEntity;
use Cicada\Frontend\Theme\FrontendPluginRegistry;
use Cicada\Frontend\Theme\ThemeEntity;
use Cicada\Frontend\Theme\ThemeService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'theme:change',
    description: 'Change the active theme for a sales channel',
)]
#[Package('frontend')]
class ThemeChangeCommand extends Command
{
    private readonly Context $context;

    private SymfonyStyle $io;

    /**
     * @internal
     */
    public function __construct(
        private readonly ThemeService $themeService,
        private readonly FrontendPluginRegistry $pluginRegistry,
        private readonly EntityRepository $channelRepository,
        private readonly EntityRepository $themeRepository
    ) {
        parent::__construct();
        $this->context = Context::createCLIContext();
    }

    protected function configure(): void
    {
        $this->addArgument('theme-name', InputArgument::OPTIONAL, 'Technical theme name');
        $this->addOption('sales-channel', 's', InputOption::VALUE_REQUIRED, 'Sales Channel ID. Can not be used together with --all.');
        $this->addOption('all', null, InputOption::VALUE_NONE, 'Set theme for all sales channel Can not be used together with -s');
        $this->addOption('no-compile', null, InputOption::VALUE_NONE, 'Skip theme compiling');
        $this->addOption('sync', null, InputOption::VALUE_NONE, 'Compile the theme synchronously');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $themeName = $input->getArgument('theme-name');
        $channelOption = $input->getOption('sales-channel');

        $this->io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        if ($input->getOption('sales-channel') && $input->getOption('all')) {
            $this->io->error('You can use either --sales-channel or --all, not both at the same time.');

            return self::INVALID;
        }

        if (!$themeName) {
            $question = new ChoiceQuestion('Please select a theme:', $this->getThemeChoices());
            $themeName = $helper->ask($input, $output, $question);
        }
        \assert(\is_string($themeName));

        /** @var ChannelCollection $channels */
        $channels = $this->channelRepository->search((new Criteria())->addFilter(new EqualsFilter('typeId', Defaults::CHANNEL_TYPE_WEB)), $this->context)->getEntities();

        if ($input->getOption('all')) {
            $selectedChannel = $channels;
        } else {
            if (!$channelOption) {
                $question = new ChoiceQuestion('Please select a sales channel:', $this->getChannelChoices($channels));
                $answer = $helper->ask($input, $output, $question);
                $channelOption = $this->parseChannelAnswer($answer);

                if ($channelOption === null) {
                    return self::INVALID;
                }
            }

            if (!$channels->has($channelOption)) {
                $this->io->error('Could not find sales channel with ID ' . $channelOption);

                return self::INVALID;
            }
            $selectedChannel = [$channels->get($channelOption)];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', $themeName));

        /** @var ThemeEntity|null $theme */
        $theme = $this->themeRepository->search($criteria, $this->context)->first();

        if ($theme === null) {
            $this->io->error('Invalid theme name');

            return self::INVALID;
        }

        if ($input->getOption('sync')) {
            $this->context->addState(ThemeService::STATE_NO_QUEUE);
        }

        /** @var ChannelEntity $channel */
        foreach ($selectedChannel as $channel) {
            $this->io->writeln(
                \sprintf('Set and compiling theme "%s" (%s) as new theme for sales channel "%s"', $themeName, $theme->getId(), $channel->getName())
            );

            $this->themeService->assignTheme(
                $theme->getId(),
                $channel->getId(),
                $this->context,
                $input->getOption('no-compile')
            );
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string>
     */
    protected function getChannelChoices(ChannelCollection $channels): array
    {
        $choices = [];

        foreach ($channels as $channel) {
            $choices[] = $channel->getName() . ' | ' . $channel->getId();
        }

        return $choices;
    }

    /**
     * @return array<string>
     */
    protected function getThemeChoices(): array
    {
        $choices = [];

        foreach ($this->pluginRegistry->getConfigurations()->getThemes() as $theme) {
            $choices[] = $theme->getTechnicalName();
        }

        return $choices;
    }

    private function parseChannelAnswer(string $answer): ?string
    {
        $parts = explode('|', $answer);
        $channelId = trim(array_pop($parts));

        if (!$channelId) {
            $this->io->error('Invalid answer');

            return null;
        }

        return $channelId;
    }
}
