<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\CheckMonitor;

use App\Monitoring\Application\Service\AlertNotificationService;
use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Model\Monitor\MonitorStatus;
use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use App\Monitoring\Domain\Service\TelemetryBufferInterface;
use App\Monitoring\Domain\Service\UrlCheckerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles the CheckMonitorCommand by performing the actual URL check.
 *
 * This handler:
 * 1. Fetches the monitor from the repository
 * 2. Performs the HTTP check via UrlCheckerInterface
 * 3. Buffers the result via TelemetryBufferInterface
 * 4. Updates the monitor's nextCheckAt timestamp
 */
#[AsMessageHandler]
final readonly class CheckMonitorHandler
{
    public function __construct(
        private AlertNotificationService $alertNotificationService,
        private MonitorRepositoryInterface $monitorRepository,
        private UrlCheckerInterface $urlChecker,
        private TelemetryBufferInterface $telemetryBuffer,
    ) {
    }

    public function __invoke(CheckMonitorCommand $command): void
    {
        $monitor = $this->monitorRepository->find(
            MonitorId::fromString($command->monitorId)
        );

        if ($monitor === null) {
            return; // Monitor was deleted while in queue
        }

        if ($monitor->status !== MonitorStatus::ACTIVE) {
            return; // Monitor was paused while in queue
        }

        // Perform the HTTP check (abstracted via interface)
        $result = $this->urlChecker->check($monitor);

        // Buffer the result for bulk ingestion (abstracted via interface)
        $this->telemetryBuffer->push($result);

        // Update monitor's state
        $isSuccess = $result->statusCode >= 200 && $result->statusCode < 300;
        $monitor->markChecked($result->checkedAt, $isSuccess);
        $this->alertNotificationService->checkAndNotify($monitor);

        $this->monitorRepository->save($monitor);
    }
}
