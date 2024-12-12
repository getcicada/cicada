<?php declare(strict_types=1);

namespace Cicada\Core\Maintenance\Channel\Command;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Adapter\Console\CicadaStyle;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\WriteConstraintViolationException;
use Cicada\Core\Maintenance\Channel\Service\ChannelCreator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal should be used over the CLI only
 */
#[AsCommand(
    name: 'sales-channel:create',
    description: 'Creates a new sales channel',
)]
#[Package('core')]
class ChannelCreateCommand extends Command
{
    public function __construct(
        private readonly ChannelCreator $channelCreator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Id for the sales channel', Uuid::randomHex())
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Name for the application')
            ->addOption('languageId', null, InputOption::VALUE_REQUIRED, 'Default language', Defaults::LANGUAGE_SYSTEM)
            ->addOption('typeId', null, InputOption::VALUE_OPTIONAL, 'Sales channel type id')
            ->addOption('navigationCategoryId', null, InputOption::VALUE_REQUIRED, 'Default Navigation Category')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getOption('id');
        $typeId = $input->getOption('typeId');

        $io = new CicadaStyle($input, $output);

        try {
            $accessKey = $this->channelCreator->createChannel(
                $id,
                $input->getOption('name') ?? 'Headless',
                $typeId ?? $this->getTypeId(),
                $input->getOption('languageId'),
                $input->getOption('navigationCategoryId'),
                null,
                $this->getChannelConfiguration($input, $output)
            );

            $io->success('Channel has been created successfully.');
        } catch (WriteException $exception) {
            $io->error('Something went wrong.');

            $messages = [];
            foreach ($exception->getExceptions() as $err) {
                if ($err instanceof WriteConstraintViolationException) {
                    foreach ($err->getViolations() as $violation) {
                        $messages[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
                    }
                }
            }

            $io->listing($messages);

            return self::SUCCESS;
        }

        $io->text('Access tokens:');

        $table = new Table($output);
        $table->setHeaders(['Key', 'Value']);

        $table->addRows([
            ['Access key', $accessKey],
        ]);

        $table->render();

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getChannelConfiguration(InputInterface $input, OutputInterface $output): array
    {
        return [];
    }

    protected function getTypeId(): string
    {
        return Defaults::CHANNEL_TYPE_API;
    }
}
