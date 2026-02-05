<?php

declare(strict_types=1);

namespace App\Telemetry\Infrastructure\Repository;

use Doctrine\DBAL\Connection;

/**
 * High-performance read repository for telemetry data visualization.
 * Leverages the three-tier aggregation strategy (Raw, Hourly, Daily).
 */
final readonly class TelemetryReadRepository
{
    public function __construct(
        private Connection $connection
    ) {
    }

    /**
     * Fetches 24h averages for all monitors in a single query.
     */
    public function getBulkLatencyAverages(): array
    {
        $start = new \DateTimeImmutable('-24 hours');

        return $this->connection->fetchAllKeyValue(
            'SELECT monitor_id, AVG(avg_latency_ms) as avg_latency
             FROM ping_stats_hourly
             WHERE bucket_time >= ?
             GROUP BY monitor_id',
            [$start->format('Y-m-d H:i:s')]
        );
    }

    /**
     * Gets latency metrics for a specific monitor over a given range.
     * Automatically switches tiers based on theRequested range.
     */
    public function getLatencyHistory(?string $monitorId, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $diff = $end->getTimestamp() - $start->getTimestamp();

        if ($diff <= 7200) { // <= 2 hours -> Raw data
            return $this->getRawLatency($monitorId, $start, $end);
        } elseif ($diff <= 604800) { // <= 7 days -> Hourly stats
            return $this->getHourlyLatency($monitorId, $start, $end);
        } else { // > 7 days -> Daily stats
            return $this->getDailyLatency($monitorId, $start, $end);
        }
    }

    public function getGlobalStats(): array
    {
        return $this->connection->fetchAssociative(
            "SELECT 
                (SELECT COUNT(*) FROM monitors) as total_monitors,
                (SELECT COUNT(*) FROM monitors WHERE health_status = 'up') as up_count,
                (SELECT COUNT(*) FROM monitors WHERE health_status = 'down') as down_count,
                COALESCE((SELECT AVG(latency_ms) FROM ping_results WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)), 0) as avg_latency_24h,
                (SELECT COUNT(*) FROM ping_results WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as total_pings_24h,
                (SELECT COUNT(*) FROM ping_results WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND is_successful = 1) as success_pings_24h"
        ) ?: [];
    }

    private function getRawLatency(?string $monitorId, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $sql = 'SELECT created_at as timestamp, AVG(latency_ms) as value 
                FROM ping_results 
                WHERE created_at BETWEEN ? AND ?';
        $params = [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];

        if ($monitorId) {
            $sql .= ' AND monitor_id = ?';
            $params[] = $monitorId;
        }

        $sql .= ' GROUP BY created_at ORDER BY created_at ASC';

        return $this->connection->fetchAllAssociative($sql, $params);
    }

    private function getHourlyLatency(?string $monitorId, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $sql = 'SELECT bucket_time as timestamp, AVG(avg_latency_ms) as value 
                FROM ping_stats_hourly 
                WHERE bucket_time BETWEEN ? AND ?';
        $params = [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];

        if ($monitorId) {
            $sql .= ' AND monitor_id = ?';
            $params[] = $monitorId;
        }

        $sql .= ' GROUP BY bucket_time ORDER BY bucket_time ASC';

        return $this->connection->fetchAllAssociative($sql, $params);
    }

    private function getDailyLatency(?string $monitorId, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $sql = 'SELECT bucket_time as timestamp, AVG(avg_latency_ms) as value 
                FROM ping_stats_daily 
                WHERE bucket_time BETWEEN ? AND ?';
        $params = [$start->format('Y-m-d'), $end->format('Y-m-d')];

        if ($monitorId) {
            $sql .= ' AND monitor_id = ?';
            $params[] = $monitorId;
        }

        $sql .= ' GROUP BY bucket_time ORDER BY bucket_time ASC';

        return $this->connection->fetchAllAssociative($sql, $params);
    }
}
