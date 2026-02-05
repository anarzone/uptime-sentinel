<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Dto;

use App\Monitoring\Domain\Model\Monitor\Monitor;

final readonly class MonitorResponseDto
{
    public function __construct(
        public string $id,
        public string $name,
        public string $url,
        public string $method,
        public int $intervalSeconds,
        public int $timeoutSeconds,
        public string $status,
        public string $healthStatus,
        public int $expectedStatusCode,
        public ?array $headers,
        public ?string $body,
        public ?string $lastCheckedAt,
        public string $nextCheckAt,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(Monitor $monitor): self
    {
        return new self(
            $monitor->id->toString(),
            $monitor->name,
            $monitor->url->value,
            $monitor->method->value,
            $monitor->intervalSeconds,
            $monitor->timeoutSeconds,
            $monitor->status->value,
            $monitor->healthStatus->value,
            $monitor->expectedStatusCode,
            $monitor->headers,
            $monitor->body,
            $monitor->lastCheckedAt?->format(\DateTimeInterface::ATOM),
            $monitor->nextCheckAt->format(\DateTimeInterface::ATOM),
            $monitor->createdAt->format(\DateTimeInterface::ATOM),
            $monitor->updatedAt->format(\DateTimeInterface::ATOM),
        );
    }

    /**
     * @param array<Monitor> $monitors
     *
     * @return array<self>
     */
    public static function fromEntities(array $monitors): array
    {
        return array_map(fn (Monitor $monitor) => self::fromEntity($monitor), $monitors);
    }
}
