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

    public function getLatencyHistory(?string $monitorId, \DateTimeImmutable $start, \DateTimeImmutable $end, ?string $ownerId = null): array
    {
        $diff = $end->getTimestamp() - $start->getTimestamp();

        if ($diff <= 7200) { // <= 2 hours -> Raw data
            return $this->getRawLatency($monitorId, $start, $end, $ownerId);
        } elseif ($diff <= 604800) { // <= 7 days -> Hourly stats
            return $this->getHourlyLatency($monitorId, $start, $end, $ownerId);
        } else { // > 7 days -> Daily stats
            return $this->getDailyLatency($monitorId, $start, $end, $ownerId);
        }
    }

    public function getGlobalStats(?string $ownerId = null): array
    {
        $quotedOwnerId = $ownerId ? $this->connection->quote($ownerId) : null;

        $monitorFilter = $ownerId ? " WHERE owner_id = $quotedOwnerId" : '';

        $pingResultBase = 'FROM ping_results pr';
        if ($ownerId) {
            $pingResultBase .= " INNER JOIN monitors m ON pr.monitor_id = m.id WHERE m.owner_id = $quotedOwnerId";
            $pingResultFilter = ' AND pr.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)';
        } else {
            $pingResultFilter = ' WHERE pr.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)';
        }

        return $this->connection->fetchAssociative(
            "SELECT 
                (SELECT COUNT(*) FROM monitors$monitorFilter) as total_monitors,
                (SELECT COUNT(*) FROM monitors WHERE health_status = 'up'".($ownerId ? " AND owner_id = $quotedOwnerId" : '').") as up_count,
                (SELECT COUNT(*) FROM monitors WHERE health_status = 'down'".($ownerId ? " AND owner_id = $quotedOwnerId" : '').") as down_count,
                COALESCE((SELECT AVG(pr.latency_ms) $pingResultBase$pingResultFilter), 0) as avg_latency_24h,
                (SELECT COUNT(*) $pingResultBase$pingResultFilter) as total_pings_24h,
                (SELECT COUNT(*) $pingResultBase$pingResultFilter AND pr.is_successful = 1) as success_pings_24h"
        ) ?: [];
    }

    private function getRawLatency(?string $monitorId, \DateTimeImmutable $start, \DateTimeImmutable $end, ?string $ownerId = null): array
    {
        $sql = 'SELECT pr.created_at as timestamp, AVG(pr.latency_ms) as value 
                FROM ping_results pr';
        $params = [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];

        if ($ownerId) {
            $sql .= ' INNER JOIN monitors m ON pr.monitor_id = m.id';
        }

        $sql .= ' WHERE pr.created_at BETWEEN ? AND ?';

        if ($monitorId) {
            $sql .= ' AND pr.monitor_id = ?';
            $params[] = $monitorId;
        }

        if ($ownerId) {
            $sql .= ' AND m.owner_id = ?';
            $params[] = $ownerId;
        }

        $sql .= ' GROUP BY pr.created_at ORDER BY pr.created_at ASC';

        return $this->connection->fetchAllAssociative($sql, $params);
    }

    private function getHourlyLatency(?string $monitorId, \DateTimeImmutable $start, \DateTimeImmutable $end, ?string $ownerId = null): array
    {
        $sql = 'SELECT psh.bucket_time as timestamp, AVG(psh.avg_latency_ms) as value 
                FROM ping_stats_hourly psh';
        $params = [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];

        if ($ownerId) {
            $sql .= ' INNER JOIN monitors m ON psh.monitor_id = m.id';
        }

        $sql .= ' WHERE psh.bucket_time BETWEEN ? AND ?';

        if ($monitorId) {
            $sql .= ' AND psh.monitor_id = ?';
            $params[] = $monitorId;
        }

        if ($ownerId) {
            $sql .= ' AND m.owner_id = ?';
            $params[] = $ownerId;
        }

        $sql .= ' GROUP BY psh.bucket_time ORDER BY psh.bucket_time ASC';

        return $this->connection->fetchAllAssociative($sql, $params);
    }

    private function getDailyLatency(?string $monitorId, \DateTimeImmutable $start, \DateTimeImmutable $end, ?string $ownerId = null): array
    {
        $sql = 'SELECT psd.bucket_time as timestamp, AVG(psd.avg_latency_ms) as value 
                FROM ping_stats_daily psd';
        $params = [$start->format('Y-m-d'), $end->format('Y-m-d')];

        if ($ownerId) {
            $sql .= ' INNER JOIN monitors m ON psd.monitor_id = m.id';
        }

        $sql .= ' WHERE psd.bucket_time BETWEEN ? AND ?';

        if ($monitorId) {
            $sql .= ' AND psd.monitor_id = ?';
            $params[] = $monitorId;
        }

        if ($ownerId) {
            $sql .= ' AND m.owner_id = ?';
            $params[] = $ownerId;
        }

        $sql .= ' GROUP BY psd.bucket_time ORDER BY psd.bucket_time ASC';

        return $this->connection->fetchAllAssociative($sql, $params);
    }
}
