<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Model\Alert;

use App\Monitoring\Domain\Model\Monitor\Monitor;
use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Model\Notification\NotificationChannel;
use App\Monitoring\Infrastructure\Persistence\EscalationPolicyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EscalationPolicyRepository::class)]
#[ORM\Table(name: 'escalation_policies')]
#[ORM\Index(name: 'idx_monitor_level', columns: ['monitor_id_uuid', 'level'])]
final class EscalationPolicy
{
    #[ORM\Embedded(class: EscalationPolicyId::class, columnPrefix: false)]
    public readonly EscalationPolicyId $id;

    #[ORM\Embedded(class: MonitorId::class, columnPrefix: 'monitor_id_')]
    public readonly MonitorId $monitorId;

    #[ORM\Column(type: Types::INTEGER)]
    public readonly int $level; // 1 = first escalation, 2 = second, etc.

    #[ORM\Column(type: Types::INTEGER)]
    public readonly int $consecutiveFailures;

    #[ORM\ManyToOne(targetEntity: NotificationChannel::class)]
    #[ORM\JoinColumn(name: 'notification_channel_id', referencedColumnName: 'uuid', nullable: false)]
    public private(set) NotificationChannel $notificationChannel;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    public private(set) bool $isEnabled = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public readonly \DateTimeImmutable $createdAt;

    public function __construct(
        EscalationPolicyId $id,
        MonitorId $monitorId,
        int $level,
        int $consecutiveFailures,
        NotificationChannel $notificationChannel,
    ) {
        $this->id = $id;
        $this->monitorId = $monitorId;
        $this->level = $level;
        $this->consecutiveFailures = $consecutiveFailures;
        $this->notificationChannel = $notificationChannel;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function create(
        MonitorId $monitorId,
        int $level,
        int $consecutiveFailures,
        NotificationChannel $notificationChannel,
    ): self {
        return new self(
            id: EscalationPolicyId::generate(),
            monitorId: $monitorId,
            level: $level,
            consecutiveFailures: $consecutiveFailures,
            notificationChannel: $notificationChannel,
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

    /**
     * Check if this policy matches the given monitor.
     */
    public function matches(Monitor $monitor): bool
    {
        if (!$this->isEnabled) {
            return false;
        }

        // Check if policy applies to this monitor
        if (!$this->monitorId->equals($monitor->id)) {
            return false;
        }

        // Check if failure threshold has been reached
        return $monitor->consecutiveFailures >= $this->consecutiveFailures;
    }

    /**
     * Check if this policy should trigger at the exact failure count.
     */
    public function shouldTrigger(Monitor $monitor): bool
    {
        return $this->matches($monitor)
            && $monitor->consecutiveFailures === $this->consecutiveFailures;
    }
}
