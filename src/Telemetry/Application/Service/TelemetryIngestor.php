<?php

declare(strict_types=1);

namespace App\Telemetry\Application\Service;

use App\Telemetry\Model\PingResultDto;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

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
        $processingKey = $this->getProcessingKey();

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
    }

    /**
     * Get the key for the processing list.
     */
    private function getProcessingKey(): string
    {
        return $this->bufferKey.':processing';
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
            // LMOVE atomically pops from source and pushes to destination
            $item = $this->redis->lmove($this->bufferKey, $processingKey, 'RIGHT', 'LEFT');

            if ($item === null || $item === false || !\is_string($item)) {
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
