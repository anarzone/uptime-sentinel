<?php

declare(strict_types=1);

namespace App\Telemetry\Model;

/**
 * DTO representing a raw ping result in the Telemetry context.
 * decoupled from the Monitoring context.
 */
final readonly class PingResultDto
{
    public function __construct(
        public string $monitorId,
        public int $statusCode,
        public int $latencyMs,
        public bool $isSuccessful,
        public \DateTimeImmutable $createdAt,
    ) {
    }

    /**
     * @param array{monitor_id: string, status_code: int, latency_ms: int, is_success: bool, checked_at: string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            monitorId: $data['monitor_id'],
            statusCode: $data['status_code'],
            latencyMs: $data['latency_ms'],
            isSuccessful: $data['is_success'],
            createdAt: new \DateTimeImmutable($data['checked_at']),
        );
    }
}
