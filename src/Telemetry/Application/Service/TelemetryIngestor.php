<?php

declare(strict_types=1);

namespace App\Telemetry\Application\Service;

use App\Telemetry\Model\PingResultDto;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Redis;

/**
 * High-performance telemetry ingestor with resilient queue pattern.
 *
 * Uses LMOVE (atomic pop-and-push) for crash safety:
 * 1. Items are moved from `buffer` to `processing` atomically.
 * 2. After successful SQL insert, the `processing` list is cleared.
 * 3. On startup, orphaned items in `processing` are recovered.
 */
final readonly class TelemetryIngestor
{
    private const int DEFAULT_BATCH_SIZE = 1000;

    public function __construct(
        private \Redis $redis,
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
        $processingKey = self::getProcessingKey($this->bufferKey);

        try {
            // Step 1: Recover any orphaned items from a previous crash
            $this->recoverOrphanedItems($processingKey);

            // Step 2: Move items atomically from buffer to processing list
            $batch = $this->moveToProcessing($batchSize, $processingKey);

            if (empty($batch)) {
                return 0;
            }

            // Step 3: Bulk insert into MySQL
            $this->bulkInsert($batch);

            // Step 4: Clear the processing list (items are now safely in DB)
            $this->redis->del($processingKey);

            $this->logger->info('Telemetry ingested', [
                'rows' => \count($batch),
                'buffer_remaining' => $this->redis->llen($this->bufferKey),
            ]);

            return \count($batch);
        } catch (\Throwable $e) {
            $this->logger->error('Telemetry ingestion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 0;
        }
    }

    /**
     * Get the key for the processing list.
     */
    public static function getProcessingKey(string $bufferKey): string
    {
        return $bufferKey.':processing';
    }

    /**
     * Recover orphaned items from a previous crash.
     *
     * If the ingestor crashed after moving items to processing but before
     * committing the SQL transaction, those items would be "orphaned".
     * This method re-queues them to the main buffer for reprocessing.
     */
    private function recoverOrphanedItems(string $processingKey): void
    {
        $orphanCount = (int) $this->redis->llen($processingKey);

        if ($orphanCount === 0) {
            return;
        }

        $this->logger->warning('Recovering orphaned telemetry items', [
            'count' => $orphanCount,
        ]);

        // Move all orphaned items back to the main buffer (FIFO order, push to right)
        while ($this->redis->llen($processingKey) > 0) {
            $this->redis->lmove($processingKey, $this->bufferKey, 'LEFT', 'RIGHT');
        }
    }

    /**
     * Move items from the buffer to the processing list atomically.
     *
     * @return list<PingResultDto>
     */
    private function moveToProcessing(int $batchSize, string $processingKey): array
    {
        $batch = [];

        for ($i = 0; $i < $batchSize; ++$i) {
            // lmove atomically pops from source and pushes to destination
            $item = $this->redis->lmove($this->bufferKey, $processingKey, 'RIGHT', 'LEFT');

            if ($item === false || !\is_string($item)) {
                break;
            }

            $data = json_decode($item, true);
            if (!\is_array($data) || !$this->validateData($data)) {
                $this->logger->error('Invalid telemetry data format', ['data' => $item]);
                continue;
            }

            $batch[] = PingResultDto::fromArray($data);
        }

        return $batch;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateData(array $data): bool
    {
        $requiredKeys = ['monitor_id', 'status_code', 'latency_ms', 'is_success', 'checked_at'];
        if (array_any($requiredKeys, fn ($key) => !\array_key_exists($key, $data))) {
            return false;
        }

        return \is_string($data['monitor_id'])
            && \is_int($data['status_code'])
            && \is_int($data['latency_ms'])
            && \is_bool($data['is_success'])
            && \is_string($data['checked_at']);
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

        $this->connection->transactional(function (Connection $conn) use ($batch) {
            $placeholders = [];
            $params = [];

            foreach ($batch as $result) {
                $placeholders[] = '(?, ?, ?, ?, ?, ?)';
                $params[] = new \Symfony\Component\Uid\UuidV7()->toRfc4122();
                $params[] = $result->monitorId;
                $params[] = $result->statusCode;
                $params[] = $result->latencyMs;
                $params[] = $result->isSuccessful ? 1 : 0;
                $params[] = $result->createdAt->format('Y-m-d H:i:s');
            }

            $sql = \sprintf(
                'INSERT INTO ping_results (id, monitor_id, status_code, latency_ms, is_successful, created_at) VALUES %s',
                implode(', ', $placeholders)
            );

            $conn->executeStatement($sql, $params);
        });
    }
}
