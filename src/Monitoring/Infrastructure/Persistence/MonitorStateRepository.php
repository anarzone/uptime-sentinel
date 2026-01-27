<?php

declare(strict_types=1);

namespace App\Monitoring\Infrastructure\Persistence;

use App\Monitoring\Domain\Model\Monitor\MonitorHealth;
use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Repository\MonitorStateRepositoryInterface;
use Redis;

/**
 * Tracks monitor state transitions in Redis for notification detection.
 *
 * This repository stores the previous health status of monitors to detect
 * transitions (e.g., DOWN → UP for recovery notifications).
 */
final readonly class MonitorStateRepository implements MonitorStateRepositoryInterface
{
    private const string KEY_PREFIX = 'monitor:state:';
    private const int DEFAULT_TTL = 86400; // 24 hours

    public function __construct(
        private \Redis $redis,
        private int $ttl = self::DEFAULT_TTL,
    ) {
    }

    /**
     * Get the last known health status for a monitor.
     */
    public function getLastHealthStatus(MonitorId $monitorId): ?MonitorHealth
    {
        $data = $this->redis->get(self::KEY_PREFIX.$monitorId->toString());
        if (!$data) {
            return null;
        }

        $health = json_decode($data, true)['health'] ?? null;
        if ($health === null) {
            return null;
        }

        return MonitorHealth::from($health);
    }

    /**
     * Save the current health status for a monitor.
     *
     * This should be called AFTER checking the monitor to record its new state.
     */
    public function saveHealthStatus(MonitorId $monitorId, MonitorHealth $health): void
    {
        $data = json_encode([
            'health' => $health->value,
            'timestamp' => time(),
        ]);

        $this->redis->setex(
            self::KEY_PREFIX.$monitorId->toString(),
            $this->ttl,
            $data
        );
    }

    /**
     * Clear the stored state for a monitor.
     *
     * Useful when a monitor is deleted or reset.
     */
    public function clear(MonitorId $monitorId): void
    {
        $this->redis->del(self::KEY_PREFIX.$monitorId->toString());
    }
}
