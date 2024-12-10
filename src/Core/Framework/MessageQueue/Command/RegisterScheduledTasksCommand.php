<?php declare(strict_types=1);

namespace Cicada\Core\Framework\MessageQueue\Command;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\Registry\TaskRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'scheduled-task:register',
    description: 'Registers all scheduled tasks',
)]
#[Package('services-settings')]
class RegisterScheduledTasksCommand extends Command
{
    /**
     * @internal
     */
    public function __construct(private readonly TaskRegistry $taskRegistry)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Registering scheduled tasks ...');
        $this->taskRegistry->registerTasks();
        $output->writeln('Done!');

        return self::SUCCESS;
    }
}