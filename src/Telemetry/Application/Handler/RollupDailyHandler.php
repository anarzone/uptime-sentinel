<?php

declare(strict_types=1);

namespace App\Telemetry\Application\Handler;

use App\Telemetry\Application\Message\RollupDailyMessage;
use App\Telemetry\Application\Service\TelemetryRollupService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles RollupDailyMessage by aggregating the previous day's hourly stats.
 */
#[AsMessageHandler]
final readonly class RollupDailyHandler
{
    public function __construct(
        private TelemetryRollupService $rollupService,
    ) {
    }

    public function __invoke(RollupDailyMessage $message): void
    {
        // Aggregate the previous day (e.g., if it's 00:10 on Jan 2nd, aggregate Jan 1st)
        $previousDay = new \DateTimeImmutable('-1 day');
        $this->rollupService->aggregateDaily($previousDay);
    }
}
