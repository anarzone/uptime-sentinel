<?php

declare(strict_types=1);

namespace App\Telemetry\Application\Service;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

/**
 * Handles data aggregation (rollup) from raw ping_results to aggregate tables.
 */
final readonly class TelemetryRollupService
{
    public function __construct(
        private Connection $connection,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Aggregate raw ping_results into hourly stats for a given hour.
     * Idempotent: Uses ON DUPLICATE KEY UPDATE.
     */
    public function aggregateHourly(\DateTimeImmutable $hour): int
    {
        $startOfHour = $hour->setTime((int) $hour->format('H'), 0, 0);
        $endOfHour = $startOfHour->modify('+1 hour');

        $sql = <<<SQL
                INSERT INTO ping_stats_hourly (monitor_id, bucket_time, ping_count, success_count, avg_latency_ms, max_latency_ms)
                SELECT
                    monitor_id,
                    :bucket_time AS bucket_time,
                    COUNT(*) AS ping_count,
                    SUM(CASE WHEN is_successful = 1 THEN 1 ELSE 0 END) AS success_count,
                    ROUND(AVG(latency_ms)) AS avg_latency_ms,
                    MAX(latency_ms) AS max_latency_ms
                FROM ping_results
                WHERE created_at >= :start_time AND created_at < :end_time
                GROUP BY monitor_id
                ON DUPLICATE KEY UPDATE
                    ping_count = VALUES(ping_count),
                    success_count = VALUES(success_count),
                    avg_latency_ms = VALUES(avg_latency_ms),
                    max_latency_ms = VALUES(max_latency_ms)
            SQL;

        $affectedRows = (int) $this->connection->executeStatement($sql, [
            'bucket_time' => $startOfHour->format('Y-m-d H:i:s'),
            'start_time' => $startOfHour->format('Y-m-d H:i:s'),
            'end_time' => $endOfHour->format('Y-m-d H:i:s'),
        ]);

        $this->logger->info('Hourly rollup completed', [
            'hour' => $startOfHour->format('Y-m-d H:00'),
            'monitors_affected' => $affectedRows,
        ]);

        return $affectedRows;
    }

    /**
     * Aggregate hourly stats into daily stats for a given day.
     * Idempotent: Uses ON DUPLICATE KEY UPDATE.
     */
    public function aggregateDaily(\DateTimeImmutable $day): int
    {
        $startOfDay = $day->setTime(0, 0, 0);
        $endOfDay = $startOfDay->modify('+1 day');

        $sql = <<<SQL
                INSERT INTO ping_stats_daily (monitor_id, bucket_time, ping_count, success_count, avg_latency_ms, max_latency_ms)
                SELECT
                    monitor_id,
                    :bucket_time AS bucket_time,
                    SUM(ping_count) AS ping_count,
                    SUM(success_count) AS success_count,
                    ROUND(AVG(avg_latency_ms)) AS avg_latency_ms,
                    MAX(max_latency_ms) AS max_latency_ms
                FROM ping_stats_hourly
                WHERE bucket_time >= :start_time AND bucket_time < :end_time
                GROUP BY monitor_id
                ON DUPLICATE KEY UPDATE
                    ping_count = VALUES(ping_count),
                    success_count = VALUES(success_count),
                    avg_latency_ms = VALUES(avg_latency_ms),
                    max_latency_ms = VALUES(max_latency_ms)
            SQL;

        $affectedRows = (int) $this->connection->executeStatement($sql, [
            'bucket_time' => $startOfDay->format('Y-m-d'),
            'start_time' => $startOfDay->format('Y-m-d H:i:s'),
            'end_time' => $endOfDay->format('Y-m-d H:i:s'),
        ]);

        $this->logger->info('Daily rollup completed', [
            'day' => $startOfDay->format('Y-m-d'),
            'monitors_affected' => $affectedRows,
        ]);

        return $affectedRows;
    }
}
