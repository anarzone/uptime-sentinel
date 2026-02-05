<?php

declare(strict_types=1);

namespace App\Telemetry\Domain\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ping_results')]
#[ORM\Index(name: 'idx_monitor_created', columns: ['monitor_id', 'created_at'])]
class PingResult
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\Id]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $monitorId;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $statusCode;

    #[ORM\Column(type: Types::INTEGER)]
    private int $latencyMs;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isSuccessful;

    public function __construct(
        string $id,
        string $monitorId,
        int $statusCode,
        int $latencyMs,
        bool $isSuccessful,
        \DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->monitorId = $monitorId;
        $this->statusCode = $statusCode;
        $this->latencyMs = $latencyMs;
        $this->isSuccessful = $isSuccessful;
        $this->createdAt = $createdAt;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getMonitorId(): string
    {
        return $this->monitorId;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getLatencyMs(): int
    {
        return $this->latencyMs;
    }

    public function isSuccessful(): bool
    {
        return $this->isSuccessful;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
