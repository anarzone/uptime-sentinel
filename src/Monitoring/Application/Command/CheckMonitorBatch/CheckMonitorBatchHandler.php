<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\CheckMonitorBatch;

use App\Monitoring\Application\Service\AlertNotificationService;
use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use App\Monitoring\Domain\Service\TelemetryBufferInterface;
use App\Monitoring\Domain\Service\UrlCheckerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CheckMonitorBatchHandler
{
    public function __construct(
        private MonitorRepositoryInterface $monitorRepository,
        private UrlCheckerInterface $urlChecker,
        private TelemetryBufferInterface $telemetryBuffer,
        private AlertNotificationService $alertNotificationService,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(CheckMonitorBatchCommand $command): void
    {
        // 1. Fetch all monitors in ONE query (no N+1)
        $monitors = $this->monitorRepository->findByIds($command->monitorIds);

        if (empty($monitors)) {
            return;
        }

        // Create a map of monitorId -> Monitor for a quick lookup
        $monitorMap = [];
        foreach ($monitors as $monitor) {
            $monitorMap[$monitor->id->toString()] = $monitor;
        }

        // 2. Perform async/concurrent checks
        $results = $this->urlChecker->checkBatch($monitors);

        // 3. Process results as they stream in
        foreach ($results as $result) {
            try {
                // Find the corresponding monitor and update state
                $monitor = $monitorMap[$result->monitorId] ?? null;
                if ($monitor) {
                    $monitor->markChecked($result->checkedAt, $result->isSuccess);

                    // 4. Buffer the result for telemetry
                    $this->telemetryBuffer->push($result);

                    // 5. Check if any alert rules should be triggered
                    $this->alertNotificationService->checkAndNotify($monitor);
                }
            } catch (\Throwable $e) {
                $this->logger->error('Failed to process check result', [
                    'monitorId' => $result->monitorId,
                    'error' => $e->getMessage(),
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // 5. Flush all monitor updates in a single transaction
        foreach ($monitorMap as $monitor) {
            $this->monitorRepository->save($monitor);
        }
    }
}
