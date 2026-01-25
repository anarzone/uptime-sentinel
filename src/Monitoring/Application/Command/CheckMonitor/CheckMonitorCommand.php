<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\CheckMonitor;

/**
 * Command to check a monitor's URL status.
 *
 * This is dispatched by the MonitorDispatcher to RabbitMQ
 * and consumed by the CheckMonitorHandler.
 */
final readonly class CheckMonitorCommand
{
    public function __construct(
        public string $monitorId,
    ) {
    }
}
