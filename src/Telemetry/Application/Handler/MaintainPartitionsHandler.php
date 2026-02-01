<?php

declare(strict_types=1);

namespace App\Telemetry\Application\Handler;

use App\Telemetry\Application\Message\MaintainPartitionsMessage;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles MaintainPartitionsMessage by managing dynamic partitions.
 *
 * - Ensures future partitions exist (next 7 days).
 * - Drops partitions older than 30 days.
 */
#[AsMessageHandler]
final readonly class MaintainPartitionsHandler
{
    private const int RETENTION_DAYS = 30;
    private const int FUTURE_DAYS = 7;

    public function __construct(
        private Connection $connection,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(MaintainPartitionsMessage $message): void
    {
        // Partitioning is a MySQL-specific optimization in this architecture.
        // We skip it for other platforms (e.g., SQLite in tests).
        if (!$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQLPlatform) {
            return;
        }

        $this->addFuturePartitions();
        $this->dropOldPartitions();
    }

    private function addFuturePartitions(): void
    {
        $today = new \DateTimeImmutable('today');

        // Check if table is already partitioned
        $isPartitioned = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.PARTITIONS
             WHERE TABLE_NAME = 'ping_results' AND PARTITION_NAME IS NOT NULL"
        ) > 0;

        for ($i = 0; $i <= self::FUTURE_DAYS; ++$i) {
            $targetDate = $today->modify("+{$i} days");
            $partitionName = 'p'.$targetDate->format('Ymd');
            $lessThanDate = $targetDate->modify('+1 day')->format('Y-m-d');

            // Check if partition already exists
            $existing = $this->connection->fetchOne(
                "SELECT PARTITION_NAME FROM INFORMATION_SCHEMA.PARTITIONS
                 WHERE TABLE_NAME = 'ping_results' AND PARTITION_NAME = ?",
                [$partitionName]
            );

            if ($existing) {
                continue;
            }

            // Security: Use identifier quoting for partition names and quote values for dates.
            // DDL statements like ALTER TABLE don't support prepared statement parameters for structure.
            $quotedPartitionName = $this->connection->quoteSingleIdentifier($partitionName);
            $quotedLessThanDate = $this->connection->quote($lessThanDate);

            // Add new partition or initialize partitioning
            $didInit = false;
            if (!$isPartitioned) {
                // Using RANGE COLUMNS(created_at) for better pruning and clarity
                $sql = \sprintf(
                    'ALTER TABLE ping_results PARTITION BY RANGE COLUMNS(created_at) (PARTITION %s VALUES LESS THAN (%s))',
                    $quotedPartitionName,
                    $quotedLessThanDate
                );
                $isPartitioned = true;
                $didInit = true;
            } else {
                $sql = \sprintf(
                    'ALTER TABLE ping_results ADD PARTITION (PARTITION %s VALUES LESS THAN (%s))',
                    $quotedPartitionName,
                    $quotedLessThanDate
                );
            }

            $this->connection->executeStatement($sql);
            $this->logger->info('Partition updated', ['partition' => $partitionName, 'init' => $didInit]);
        }
    }

    private function dropOldPartitions(): void
    {
        $cutoffDate = (new \DateTimeImmutable('today'))->modify('-'.self::RETENTION_DAYS.' days');

        // Fetch all partitions
        $partitions = $this->connection->fetchAllAssociative(
            "SELECT PARTITION_NAME, PARTITION_DESCRIPTION
             FROM INFORMATION_SCHEMA.PARTITIONS
             WHERE TABLE_NAME = 'ping_results'
               AND PARTITION_NAME IS NOT NULL"
        );

        foreach ($partitions as $partition) {
            $partitionName = $partition['PARTITION_NAME'];
            if (!\is_string($partitionName)) {
                continue;
            }

            // Simple date-based naming: pYYYYMMDD
            if (preg_match('/^p(\d{8})$/', $partitionName, $matches)) {
                $partitionDate = \DateTimeImmutable::createFromFormat('Ymd', $matches[1]);
                if ($partitionDate && $partitionDate < $cutoffDate) {
                    $sql = \sprintf('ALTER TABLE ping_results DROP PARTITION %s', $this->connection->quoteSingleIdentifier($partitionName));
                    $this->connection->executeStatement($sql);
                    $this->logger->info('Dropped old partition', ['partition' => $partitionName]);
                }
            }
        }
    }
}
