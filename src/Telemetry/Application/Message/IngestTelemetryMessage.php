<?php

declare(strict_types=1);

namespace App\Telemetry\Application\Message;

/**
 * Message triggering telemetry ingestion.
 *
 * Dispatched by the TelemetryScheduler on a recurring basis.
 */
final readonly class IngestTelemetryMessage
{
}
