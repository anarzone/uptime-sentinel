<?php

declare(strict_types=1);

namespace App\Monitoring\Infrastructure\Persistence;

use App\Monitoring\Domain\Model\Monitor\MonitorHealth;
use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Model\Monitor\MonitorState;
use App\Monitoring\Domain\Repository\MonitorStateRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Tracks monitor state transitions in Redis for notification detection.
 *
 * This repository stores the previous health status of monitors to detect
 * transitions (e.g., DOWN → UP for recovery notifications).
 *
 * Implements graceful degradation - if Redis is unavailable, the service
 * continues operating but may send duplicate recovery notifications.
 */
final readonly class MonitorStateRepository implements MonitorStateRepositoryInterface
{
    private const string KEY_PREFIX = 'monitor:state:';
    private const int DEFAULT_TTL = 86400; // 24 hours

    public function __construct(
        private \Redis $redis,
        private int $ttl = self::DEFAULT_TTL,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Get the last known health status for a monitor.
     */
    public function getLastHealthStatus(MonitorId $monitorId): ?MonitorHealth
    {
        try {
            $data = $this->redis->get(self::KEY_PREFIX.$monitorId->toString());
            if (!$data) {
                return null;
            }

            $state = MonitorState::deserialize($data);

            return $state?->health;
        } catch (\Throwable $e) {
            $this->logger?->error('Error while fetching monitor state', [
                'monitorId' => $monitorId->toString(),
                'error' => $e->getMessage(),
                'class' => $e::class,
            ]);

            return null;  // Graceful degradation
        }
    }

    /**
     * Save the current health status for a monitor.
     *
     * This should be called AFTER checking the monitor to record its new state.
     */
    public function saveHealthStatus(MonitorId $monitorId, MonitorHealth $health): void
    {
        try {
            $state = new MonitorState($health, time());
            $this->redis->setex(
                self::KEY_PREFIX.$monitorId->toString(),
                $this->ttl,
                $state->serialize()
            );
        } catch (\Throwable $e) {
            $this->logger?->error('Error while saving monitor state', [
                'monitorId' => $monitorId->toString(),
                'error' => $e->getMessage(),
                'class' => $e::class,
            ]);
            // Fail silently - monitoring state is non-critical
        }
    }

    /**
     * Clear the stored state for a monitor.
     *
     * Useful when a monitor is deleted or reset.
     */
    public function clear(MonitorId $monitorId): void
    {
        try {
            $this->redis->del(self::KEY_PREFIX.$monitorId->toString());
        } catch (\Throwable $e) {
            $this->logger?->error('Error while clearing monitor state', [
                'monitorId' => $monitorId->toString(),
                'error' => $e->getMessage(),
                'class' => $e::class,
            ]);
        }
    }
}
