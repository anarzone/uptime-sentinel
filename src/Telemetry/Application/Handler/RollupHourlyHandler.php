<?php

declare(strict_types=1);

namespace App\Telemetry\Application\Handler;

use App\Telemetry\Application\Message\RollupHourlyMessage;
use App\Telemetry\Application\Service\TelemetryRollupService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles RollupHourlyMessage by aggregating the previous hour's data.
 */
#[AsMessageHandler]
final readonly class RollupHourlyHandler
{
    public function __construct(
        private TelemetryRollupService $rollupService,
    ) {
    }

    public function __invoke(RollupHourlyMessage $message): void
    {
        // Aggregate the previous hour (e.g., if it's 14:05, aggregate 13:00-14:00)
        $previousHour = new \DateTimeImmutable('-1 hour');
        $this->rollupService->aggregateHourly($previousHour);
    }
}
