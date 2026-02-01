<?php

declare(strict_types=1);

namespace App\Tests\Integration\Telemetry;

use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Telemetry\Application\Service\TelemetryIngestor;
use App\Telemetry\Model\PingResultDto;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

final class TelemetryIngestorTest extends KernelTestCase
{
    use ResetDatabase;

    private TelemetryIngestor $ingestor;
    private Connection $connection;
    private \Redis $redis;
    private string $bufferKey;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->ingestor = $container->get(TelemetryIngestor::class);
        $this->connection = $container->get(Connection::class);
        $this->redis = $container->get('redis_client');
        $this->bufferKey = $container->getParameter('telemetry_buffer_key');

        // Clean redis buffer before each test
        $this->redis->del($this->bufferKey);
        $this->redis->del(TelemetryIngestor::getProcessingKey($this->bufferKey));
    }

    public function testIngestMovesDataFromRedisToDatabase(): void
    {
        $monitorId = MonitorId::generate();
        $pings = [
            new PingResultDto($monitorId->toString(), 200, 100, true, new \DateTimeImmutable('2026-01-31 12:00:00')),
            new PingResultDto($monitorId->toString(), 500, 350, false, new \DateTimeImmutable('2026-01-31 12:01:00')),
        ];

        // Push to Redis
        foreach ($pings as $ping) {
            $this->redis->lpush($this->bufferKey, json_encode($ping->toArray()));
        }

        $ingestedCount = $this->ingestor->ingest(batchSize: 10);

        $this->assertSame(2, $ingestedCount);
        $this->assertSame(0, $this->redis->llen($this->bufferKey));
        $this->assertSame(0, $this->redis->llen(TelemetryIngestor::getProcessingKey($this->bufferKey)));

        // Verify in DB
        $dbCount = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM ping_results');
        $this->assertSame(2, $dbCount);
    }

    public function testRecoveryOfOrphanedItems(): void
    {
        $monitorId = MonitorId::generate();
        $ping = new PingResultDto($monitorId->toString(), 200, 150, true, new \DateTimeImmutable('2026-01-31 12:00:00'));

        // Manually put an item into the :processing list (simulating a crash)
        $this->redis->lpush(TelemetryIngestor::getProcessingKey($this->bufferKey), json_encode($ping->toArray()));

        // Ingestor should recover it and then insert it
        $ingestedCount = $this->ingestor->ingest(batchSize: 10);

        $this->assertSame(1, $ingestedCount);
        $this->assertSame(0, $this->redis->llen(TelemetryIngestor::getProcessingKey($this->bufferKey)));

        $dbCount = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM ping_results');
        $this->assertSame(1, $dbCount);
    }
}
