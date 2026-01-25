<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\UpdateMonitor;

final readonly class UpdateMonitorCommand
{
    public function __construct(
        public string $uuid,
        public ?string $name,
        public ?string $url,
        public ?string $method,
        public ?int $intervalSeconds,
        public ?int $timeoutSeconds,
        public ?int $expectedStatusCode,
        public ?array $headers,
        public ?string $body
    ) {
    }
}
