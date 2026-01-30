<?php

namespace App\Command;

use App\Service\SubscriptionsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'mail:subscriptions',
    description: 'Send all users details about the pages they subscribed to',
)]
class MailSubscriptionsCommand extends Command
{
    public function __construct(
        private readonly SubscriptionsService $subscriptionsService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('from', 'f', InputOption::VALUE_OPTIONAL, 'Date to look subscriptions', '1 week ago')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $from = $input->getOption('from');

        $numberReportsSent = $this->subscriptionsService->sendWeeklyReports($from);

        $io->success(sprintf('Report sent to %s users', $numberReportsSent));

        return Command::SUCCESS;
    }
}
