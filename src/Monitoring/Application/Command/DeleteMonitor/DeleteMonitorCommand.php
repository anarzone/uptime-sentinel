<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\DeleteMonitor;

final readonly class DeleteMonitorCommand
{
    public function __construct(
        public string $uuid,
        public ?string $requesterId = null,
    ) {
    }
}
