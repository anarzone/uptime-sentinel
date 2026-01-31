<?php

declare(strict_types=1);

namespace App\Telemetry\Application\Handler;

use App\Telemetry\Application\Message\IngestTelemetryMessage;
use App\Telemetry\Application\Service\TelemetryIngestor;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles the IngestTelemetryMessage by calling the ingestor.
 */
#[AsMessageHandler]
final readonly class IngestTelemetryHandler
{
    public function __construct(
        private TelemetryIngestor $ingestor,
    ) {
    }

    public function __invoke(IngestTelemetryMessage $message): void
    {
        $this->ingestor->ingest();
    }
}
