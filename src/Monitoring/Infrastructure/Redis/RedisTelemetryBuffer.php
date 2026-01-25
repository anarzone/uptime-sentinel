<?php

declare(strict_types=1);

namespace App\Monitoring\Infrastructure\Redis;

use App\Monitoring\Application\Dto\CheckResultDto;
use App\Monitoring\Domain\Service\TelemetryBufferInterface;

/**
 * Redis implementation of the telemetry buffer.
 *
 * Pushes check results to a Redis list for bulk ingestion by Telemetry context.
 */
final readonly class RedisTelemetryBuffer implements TelemetryBufferInterface
{
    private const string BUFFER_KEY = 'telemetry_buffer';

    /**
     * @param object $redis Redis client instance (Predis\Client or similar)
     */
    public function __construct(
        private object $redis,
    ) {
    }

    public function push(CheckResultDto $result): void
    {
        $this->redis->lpush(self::BUFFER_KEY, [json_encode($result->toArray())]);
    }
}
