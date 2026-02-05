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
    public function __construct(
        private object $redis,
        private string $bufferKey,
    ) {
    }

    public function push(CheckResultDto $result): void
    {
        $this->redis->lpush($this->bufferKey, json_encode($result->toArray()));
    }
}
