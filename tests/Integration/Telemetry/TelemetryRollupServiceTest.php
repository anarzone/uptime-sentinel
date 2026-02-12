<?php

declare(strict_types=1);

namespace App\Tests\Integration\Telemetry;

use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Telemetry\Application\Service\TelemetryRollupService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TelemetryRollupServiceTest extends KernelTestCase
{

    private TelemetryRollupService $service;
    private Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->service = $container->get(TelemetryRollupService::class);
        $this->connection = $container->get(Connection::class);
    }

    public function testAggregateHourlyGroupsDataCorrectly(): void
    {
        $monitorId = MonitorId::generate()->toString();
        $hour = new \DateTimeImmutable('2026-01-31 10:00:00');

        // Insert raw results for the target hour
        $this->insertRawResult($monitorId, 200, 100, true, '2026-01-31 10:05:00');
        $this->insertRawResult($monitorId, 200, 200, true, '2026-01-31 10:15:00');
        $this->insertRawResult($monitorId, 500, 300, false, '2026-01-31 10:45:00');

        // Insert raw result for a DIFFERENT hour (should be ignored)
        $this->insertRawResult($monitorId, 200, 150, true, '2026-01-31 11:05:00');

        $affectedMonitors = $this->service->aggregateHourly($hour);

        $this->assertSame(1, $affectedMonitors);

        $stats = $this->connection->fetchAssociative(
            'SELECT * FROM ping_stats_hourly WHERE monitor_id = ? AND bucket_time = ?',
            [$monitorId, '2026-01-31 10:00:00']
        );

        $this->assertNotFalse($stats);
        $this->assertSame(3, $stats['ping_count']);
        $this->assertSame(2, $stats['success_count']);
        $this->assertSame(200, $stats['avg_latency_ms']); // (100 + 200 + 300) / 3 = 200
        $this->assertSame(300, $stats['max_latency_ms']);
    }

    public function testAggregateDailyGroupsHourlyStatsCorrectly(): void
    {
        $monitorId = MonitorId::generate()->toString();
        $day = new \DateTimeImmutable('2026-01-31 00:00:00');

        // Insert hourly stats for the target day
        $this->insertHourlyStat($monitorId, '2026-01-31 10:00:00', 10, 9, 100, 150);
        $this->insertHourlyStat($monitorId, '2026-01-31 11:00:00', 20, 18, 200, 250);

        // Insert hourly stat for a DIFFERENT day (should be ignored)
        $this->insertHourlyStat($monitorId, '2026-02-01 10:00:00', 10, 10, 50, 50);

        $affectedMonitors = $this->service->aggregateDaily($day);

        $this->assertSame(1, $affectedMonitors);

        $stats = $this->connection->fetchAssociative(
            'SELECT * FROM ping_stats_daily WHERE monitor_id = ? AND bucket_time = ?',
            [$monitorId, '2026-01-31']
        );

        $this->assertNotFalse($stats);
        $this->assertSame(30, $stats['ping_count']); // 10 + 20
        $this->assertSame(27, $stats['success_count']); // 9 + 18
        $this->assertSame(150, $stats['avg_latency_ms']); // (100 + 200) / 2 = 150
        $this->assertSame(250, $stats['max_latency_ms']);
    }

    private function insertRawResult(string $monitorId, int $statusCode, int $latencyMs, bool $isSuccessful, string $createdAt): void
    {
        $this->connection->executeStatement(
            'INSERT INTO ping_results (id, monitor_id, status_code, latency_ms, is_successful, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            [new \Symfony\Component\Uid\UuidV7()->toRfc4122(), $monitorId, $statusCode, $latencyMs, $isSuccessful ? 1 : 0, $createdAt]
        );
    }

    private function insertHourlyStat(string $monitorId, string $bucketTime, int $pingCount, int $successCount, int $avgLatency, int $maxLatency): void
    {
        $this->connection->executeStatement(
            'INSERT INTO ping_stats_hourly (monitor_id, bucket_time, ping_count, success_count, avg_latency_ms, max_latency_ms) VALUES (?, ?, ?, ?, ?, ?)',
            [$monitorId, $bucketTime, $pingCount, $successCount, $avgLatency, $maxLatency]
        );
    }
}
