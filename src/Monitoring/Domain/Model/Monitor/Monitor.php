<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Model\Monitor;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Monitoring\Infrastructure\Persistence\MonitorRepository::class)]
#[ORM\Table(name: 'monitors')]
final class Monitor
{
    #[ORM\Embedded(class: MonitorId::class, columnPrefix: false)]
    public readonly MonitorId $id;

    #[ORM\Column(type: Types::STRING)]
    public private(set) string $name;

    #[ORM\Embedded(class: Url::class)]
    public private(set) Url $url;

    #[ORM\Column(type: Types::STRING, enumType: HttpMethod::class)]
    public private(set) HttpMethod $method;

    #[ORM\Column(type: Types::INTEGER)]
    public private(set) int $intervalSeconds;

    #[ORM\Column(type: Types::INTEGER)]
    public private(set) int $timeoutSeconds;

    #[ORM\Column(type: Types::STRING, enumType: MonitorStatus::class)]
    public private(set) MonitorStatus $status;

    #[ORM\Column(type: Types::INTEGER)]
    public private(set) int $expectedStatusCode;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    public private(set) ?array $headers;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public private(set) ?string $body;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $lastCheckedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $nextCheckAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public readonly \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: Types::STRING, enumType: MonitorHealth::class)]
    public private(set) MonitorHealth $healthStatus = MonitorHealth::UNKNOWN;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $lastStatusChangeAt = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    public private(set) int $consecutiveFailures = 0;

    public function __construct(
        MonitorId $id,
        string $name,
        Url $url,
        HttpMethod $method,
        int $intervalSeconds,
        int $timeoutSeconds,
        MonitorStatus $status,
        int $expectedStatusCode,
        ?array $headers,
        ?string $body,
        ?\DateTimeImmutable $lastCheckedAt,
        \DateTimeImmutable $nextCheckAt,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->url = $url;
        $this->method = $method;
        $this->intervalSeconds = $intervalSeconds;
        $this->timeoutSeconds = $timeoutSeconds;
        $this->status = $status;
        $this->expectedStatusCode = $expectedStatusCode;
        $this->headers = $headers;
        $this->body = $body;
        $this->lastCheckedAt = $lastCheckedAt;
        $this->nextCheckAt = $nextCheckAt;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function updateConfiguration(
        string $name,
        Url $url,
        HttpMethod $method,
        int $intervalSeconds,
        int $timeoutSeconds,
        int $expectedStatusCode,
        ?array $headers,
        ?string $body
    ): void {
        $this->name = $name;
        $this->url = $url;
        $this->method = $method;
        $this->intervalSeconds = $intervalSeconds;
        $this->timeoutSeconds = $timeoutSeconds;
        $this->expectedStatusCode = $expectedStatusCode;
        $this->headers = $headers;
        $this->body = $body;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function pause(): void
    {
        $this->status = MonitorStatus::PAUSED;
    }

    public function resume(): void
    {
        $this->status = MonitorStatus::ACTIVE;
    }

    public function markChecked(\DateTimeImmutable $checkedAt, bool $isSuccess): void
    {
        if ($this->status !== MonitorStatus::ACTIVE) {
            throw new \DomainException('Cannot mark check on inactive monitor');
        }

        $newHealth = $isSuccess ? MonitorHealth::UP : MonitorHealth::DOWN;

        // Track consecutive failures for alerting
        if ($isSuccess) {
            $this->consecutiveFailures = 0;
        } else {
            ++$this->consecutiveFailures;
        }

        // If status changed, record the time (for notifications)
        if ($this->healthStatus !== $newHealth) {
            $this->lastStatusChangeAt = $checkedAt;
        }

        $this->healthStatus = $newHealth;
        $this->lastCheckedAt = $checkedAt;
        $this->nextCheckAt = $checkedAt->modify("+$this->intervalSeconds seconds");
        $this->updatedAt = $checkedAt;
    }

    public function isDue(\DateTimeImmutable $now = new \DateTimeImmutable()): bool
    {
        return $this->status === MonitorStatus::ACTIVE
            && $this->nextCheckAt <= $now;
    }
}
