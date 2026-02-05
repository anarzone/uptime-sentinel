<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\CreateMonitor;

final readonly class CreateMonitorCommand
{
    public function __construct(
        public string $uuid,
        public string $name,
        public string $url,
        public string $method,
        public int $intervalSeconds,
        public int $timeoutSeconds,
        public int $expectedStatusCode,
        public ?array $headers,
        public ?string $body,
        public ?string $ownerId = null,
        public ?string $requesterId = null
    ) {
    }
}
