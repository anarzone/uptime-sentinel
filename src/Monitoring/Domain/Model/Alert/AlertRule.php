<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Model\Alert;

use App\Monitoring\Domain\Model\Monitor\MonitorId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Monitoring\Infrastructure\Persistence\AlertRuleRepository::class)]
#[ORM\Table(name: 'alert_rules')]
class AlertRule
{
    #[ORM\Id]
    #[ORM\Embedded(class: AlertRuleId::class)]
    public readonly AlertRuleId $id;

    #[ORM\Embedded(class: MonitorId::class)]
    public private(set) MonitorId $monitorId;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: AlertChannel::class)]
    public private(set) AlertChannel $channel;

    /** The notification target (email address, Slack webhook URL, etc.) */
    #[ORM\Column(type: Types::STRING, length: 255)]
    public private(set) string $target;

    /** Number of consecutive failures required before sending an alert */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 3])]
    public private(set) int $failureThreshold = 3;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    public private(set) bool $isEnabled = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public readonly \DateTimeImmutable $createdAt;

    public function __construct(
        AlertRuleId $id,
        MonitorId $monitorId,
        AlertChannel $channel,
        string $target,
        int $failureThreshold = 3,
        bool $isEnabled = true,
        ?\DateTimeImmutable $createdAt = null,
    ) {
        $this->id = $id;
        $this->monitorId = $monitorId;
        $this->channel = $channel;
        $this->target = $target;
        $this->failureThreshold = $failureThreshold;
        $this->isEnabled = $isEnabled;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
    }

    public static function create(
        MonitorId $monitorId,
        AlertChannel $channel,
        string $target,
        int $failureThreshold = 3,
    ): self {
        return new self(
            AlertRuleId::generate(),
            $monitorId,
            $channel,
            $target,
            $failureThreshold,
        );
    }

    public function enable(): void
    {
        $this->isEnabled = true;
    }

    public function disable(): void
    {
        $this->isEnabled = false;
    }

    public function updateTarget(string $target): void
    {
        $this->target = $target;
    }

    public function updateThreshold(int $failureThreshold): void
    {
        $this->failureThreshold = $failureThreshold;
    }
}
