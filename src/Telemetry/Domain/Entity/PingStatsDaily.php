<?php

declare(strict_types=1);

namespace App\Telemetry\Domain\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ping_stats_daily')]
#[ORM\Index(name: 'idx_bucket_time_daily', columns: ['bucket_time'])]
class PingStatsDaily
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $monitorId;

    #[ORM\Id]
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $bucketTime;

    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true, 'default' => 0])]
    private int $pingCount = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true, 'default' => 0])]
    private int $successCount = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true, 'default' => 0])]
    private int $avgLatencyMs = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true, 'default' => 0])]
    private int $maxLatencyMs = 0;

    public function __construct(string $monitorId, \DateTimeImmutable $bucketTime)
    {
        $this->monitorId = $monitorId;
        $this->bucketTime = $bucketTime;
    }

    public function getMonitorId(): string
    {
        return $this->monitorId;
    }

    public function getBucketTime(): \DateTimeImmutable
    {
        return $this->bucketTime;
    }

    public function getPingCount(): int
    {
        return $this->pingCount;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getAvgLatencyMs(): int
    {
        return $this->avgLatencyMs;
    }

    public function getMaxLatencyMs(): int
    {
        return $this->maxLatencyMs;
    }
}
