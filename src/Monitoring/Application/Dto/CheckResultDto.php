<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Dto;

/**
 * DTO representing the result of a URL check.
 *
 * Passed from the handler to the TelemetryBuffer.
 */
final readonly class CheckResultDto
{
    public function __construct(
        public string $monitorId,
        public int $statusCode,
        public int $latencyMs,
        public bool $isSuccess,
        public \DateTimeImmutable $checkedAt,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'monitor_id' => $this->monitorId,
            'status_code' => $this->statusCode,
            'latency_ms' => $this->latencyMs,
            'is_success' => $this->isSuccess,
            'checked_at' => $this->checkedAt->format('Y-m-d H:i:s'),
        ];
    }
}
