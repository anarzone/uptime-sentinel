<?php

declare(strict_types=1);

namespace App\Telemetry\Infrastructure\Console;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Displays the lag status across the three telemetry aggregation tiers.
 */
#[AsCommand(
    name: 'telemetry:status',
    description: 'Show the aggregation lag across telemetry tiers.',
)]
final class TelemetryStatusCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        private readonly object $redis,
        private readonly string $bufferKey,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Telemetry Status');

        $now = new \DateTimeImmutable();

        // 1. Redis Buffer Status
        $bufferCount = (int) $this->redis->llen($this->bufferKey);
        $processingCount = (int) $this->redis->llen($this->bufferKey.':processing');

        $io->section('Redis Buffers');
        $io->table(
            ['Buffer', 'Items'],
            [
                [$this->bufferKey, $bufferCount],
                [$this->bufferKey.':processing', $processingCount],
            ]
        );

        // 2. Query last timestamp from each tier
        $tiers = [
            'Tier 1 (Raw)' => 'SELECT MAX(created_at) FROM ping_results',
            'Tier 2 (Hourly)' => 'SELECT MAX(bucket_start) FROM ping_stats_hourly',
            'Tier 3 (Daily)' => 'SELECT MAX(bucket_start) FROM ping_stats_daily',
        ];

        $rows = [];
        foreach ($tiers as $tierName => $sql) {
            try {
                $lastTimestamp = $this->connection->fetchOne($sql);
                if (\is_string($lastTimestamp)) {
                    $lastDate = new \DateTimeImmutable($lastTimestamp);
                    $lag = $now->diff($lastDate);
                    $lagString = $this->formatInterval($lag);
                    $rows[] = [$tierName, $lastTimestamp, $lagString];
                } else {
                    $rows[] = [$tierName, 'N/A (No Data)', '-'];
                }
            } catch (\Exception $e) {
                $rows[] = [$tierName, 'Error: '.$e->getMessage(), '-'];
            }
        }

        $io->section('Aggregation Tier Status');
        $io->table(
            ['Tier', 'Last Record', 'Lag'],
            $rows
        );

        // 3. Health Check
        if ($bufferCount > 5000) {
            $io->warning('Buffer is accumulating! Consider scaling ingestors or checking for DB slowness.');
        }

        if ($processingCount > 0) {
            $io->caution('Processing list is not empty. There may be orphaned items from a crash.');
        }

        $io->success('Telemetry status check complete.');

        return Command::SUCCESS;
    }

    private function formatInterval(\DateInterval $interval): string
    {
        $parts = [];

        if ($interval->days > 0) {
            $parts[] = $interval->days.'d';
        }
        if ($interval->h > 0) {
            $parts[] = $interval->h.'h';
        }
        if ($interval->i > 0) {
            $parts[] = $interval->i.'m';
        }
        if (empty($parts)) {
            $parts[] = $interval->s.'s';
        }

        return implode(' ', $parts);
    }
}
