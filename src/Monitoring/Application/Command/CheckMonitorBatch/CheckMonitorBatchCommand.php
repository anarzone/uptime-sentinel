<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\CheckMonitorBatch;

final readonly class CheckMonitorBatchCommand
{
    /**
     * @param string[] $monitorIds
     */
    public function __construct(
        public array $monitorIds,
    ) {
    }
}
