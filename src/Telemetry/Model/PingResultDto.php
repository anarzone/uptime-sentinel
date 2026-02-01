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
     * Converts the DTO to a primitive array for storage or transmission.
     *
     * @return array{monitor_id: string, status_code: int, latency_ms: int, is_success: bool, checked_at: string}
     */
    public function toArray(): array
    {
        return [
            'monitor_id' => $this->monitorId,
            'status_code' => $this->statusCode,
            'latency_ms' => $this->latencyMs,
            'is_success' => $this->isSuccessful,
            'checked_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Creates a DTO instance from a primitive array.
     *
     * @param array{monitor_id: string, status_code: int, latency_ms: int, is_success: bool, checked_at: string} $data
     *
     * @throws \Exception if the date format is invalid
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
