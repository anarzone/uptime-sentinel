<?php

declare(strict_types=1);

namespace App\Telemetry\Application\Service;

use App\Telemetry\Model\PingResultDto;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

/**
 * High-performance telemetry ingestor.
 *
 * Pops check results from Redis buffer and bulk-inserts into MySQL.
 */
final readonly class TelemetryIngestor
{
    private const int DEFAULT_BATCH_SIZE = 1000;

    public function __construct(
        private object $redis,
        private Connection $connection,
        private LoggerInterface $logger,
        private string $bufferKey,
    ) {
    }

    /**
     * Ingest data from the Redis buffer into the ping_results table.
     *
     * @param int $batchSize Maximum number of items to ingest per call
     *
     * @return int Number of rows inserted
     */
    public function ingest(int $batchSize = self::DEFAULT_BATCH_SIZE): int
    {
        $batch = $this->popBatch($batchSize);

        if (empty($batch)) {
            return 0;
        }

        $this->bulkInsert($batch);

        $this->logger->info('Telemetry ingested', [
            'rows' => \count($batch),
            'buffer_remaining' => $this->redis->llen($this->bufferKey),
        ]);

        return \count($batch);
    }

    /**
     * Pop items from the Redis buffer.
     *
     * @return list<PingResultDto>
     */
    private function popBatch(int $batchSize): array
    {
        $batch = [];

        for ($i = 0; $i < $batchSize; ++$i) {
            $item = $this->redis->rpop($this->bufferKey);

            if ($item === null || !\is_string($item)) {
                break;
            }

            /** @var array{monitor_id: string, status_code: int, latency_ms: int, is_success: bool, checked_at: string} $data */
            $data = json_decode($item, true);
            $batch[] = PingResultDto::fromArray($data);
        }

        return $batch;
    }

    /**
     * Bulk insert ping results into the database.
     *
     * @param list<PingResultDto> $batch
     */
    private function bulkInsert(array $batch): void
    {
        if (empty($batch)) {
            return;
        }

        $placeholders = [];
        $params = [];

        foreach ($batch as $result) {
            $placeholders[] = '(?, ?, ?, ?, ?)';
            $params[] = $result->monitorId;
            $params[] = $result->statusCode;
            $params[] = $result->latencyMs;
            $params[] = $result->isSuccessful ? 1 : 0;
            $params[] = $result->createdAt->format('Y-m-d H:i:s');
        }

        $sql = \sprintf(
            'INSERT INTO ping_results (monitor_id, status_code, latency_ms, is_successful, created_at) VALUES %s',
            implode(', ', $placeholders)
        );

        $this->connection->executeStatement($sql, $params);
    }
}
