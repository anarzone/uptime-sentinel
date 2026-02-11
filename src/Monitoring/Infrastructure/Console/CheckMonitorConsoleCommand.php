<?php

declare(strict_types=1);

namespace App\Monitoring\Infrastructure\Console;

use App\Monitoring\Application\Service\AlertNotificationService;
use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Model\Monitor\MonitorStatus;
use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use App\Monitoring\Domain\Service\TelemetryBufferInterface;
use App\Monitoring\Domain\Service\UrlCheckerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'monitoring:check',
    description: 'Manually runs a check for a specific monitor, bypassing the queue.',
)]
final class CheckMonitorConsoleCommand extends Command
{
    public function __construct(
        private readonly MonitorRepositoryInterface $monitorRepository,
        private readonly UrlCheckerInterface $urlChecker,
        private readonly TelemetryBufferInterface $telemetryBuffer,
        private readonly AlertNotificationService $alertNotificationService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('monitorId', InputArgument::REQUIRED, 'The UUID of the monitor to check')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force check even if monitor is paused')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $monitorIdString = $input->getArgument('monitorId');
        $force = $input->getOption('force');

        try {
            $monitorId = MonitorId::fromString($monitorIdString);
        } catch (\InvalidArgumentException $e) {
            $io->error('Invalid monitor UUID: '.$e->getMessage());

            return Command::FAILURE;
        }

        $monitor = $this->monitorRepository->find($monitorId);

        if ($monitor === null) {
            $io->error('Monitor not found.');

            return Command::FAILURE;
        }

        $io->info(\sprintf('Checking monitor "%s" (%s)...', $monitor->name, (string) $monitor->url));

        if ($monitor->status !== MonitorStatus::ACTIVE && !$force) {
            $io->warning(\sprintf('Monitor is %s. Use --force to check anyway.', $monitor->status->value));

            return Command::FAILURE;
        }

        try {
            // 1. Perform check
            $startTime = microtime(true);
            $result = $this->urlChecker->check($monitor);
            $duration = microtime(true) - $startTime;

            // 2. Buffer telemetry
            $this->telemetryBuffer->push($result);

            // 3. Update monitor state
            $isSuccess = $result->statusCode === $monitor->expectedStatusCode;
            $monitor->markChecked($result->checkedAt, $isSuccess);

            // 4. Check for alerts
            $this->alertNotificationService->checkAndNotify($monitor);

            // 5. Persist
            $this->monitorRepository->save($monitor);

            $status = $isSuccess ? 'UP' : 'DOWN';
            $io->success(\sprintf(
                'Check complete. Status: %s (Code: %d, Time: %.4fs)',
                $status,
                $result->statusCode,
                $duration
            ));

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $io->error('Error performing check: '.$e->getMessage());
            $io->writeln($e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}
