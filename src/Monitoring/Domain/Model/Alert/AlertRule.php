<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Model\Alert;

use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Model\Notification\NotificationChannel;
use App\Monitoring\Infrastructure\Persistence\AlertRuleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlertRuleRepository::class)]
#[ORM\Table(name: 'alert_rules')]
class AlertRule
{
    #[ORM\Embedded(class: AlertRuleId::class, columnPrefix: false)]
    public readonly AlertRuleId $id;

    #[ORM\Embedded(class: MonitorId::class, columnPrefix: 'monitor_')]
    public readonly MonitorId $monitorId;

    #[ORM\ManyToOne(targetEntity: NotificationChannel::class)]
    #[ORM\JoinColumn(
        name: 'notification_channel_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    public private(set) NotificationChannel $notificationChannel;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: NotificationType::class)]
    public private(set) NotificationType $type = NotificationType::FAILURE;

    /** Number of consecutive failures required before sending an alert */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 3])]
    public private(set) int $failureThreshold = 3;

    /** Cooldown interval in ISO 8601 duration format (e.g., PT1H for 1 hour) */
    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    public private(set) ?string $cooldownInterval = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    public private(set) bool $isEnabled = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public readonly \DateTimeImmutable $createdAt;

    public function __construct(
        AlertRuleId $id,
        MonitorId $monitorId,
        NotificationChannel $notificationChannel,
        \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
    ) {
        $this->id = $id;
        $this->monitorId = $monitorId;
        $this->notificationChannel = $notificationChannel;
        $this->createdAt = $createdAt;
    }

    public static function create(
        MonitorId $monitorId,
        NotificationChannel $notificationChannel,
        int $failureThreshold = 3,
        NotificationType $type = NotificationType::FAILURE,
    ): self {
        $rule = new self(
            id: AlertRuleId::generate(),
            monitorId: $monitorId,
            notificationChannel: $notificationChannel,
        );
        $rule->failureThreshold = $failureThreshold;
        $rule->type = $type;

        return $rule;
    }

    public function enable(): void
    {
        $this->isEnabled = true;
    }

    public function disable(): void
    {
        $this->isEnabled = false;
    }

    public function updateThreshold(int $failureThreshold): void
    {
        $this->failureThreshold = $failureThreshold;
    }

    public function updateType(NotificationType $type): void
    {
        $this->type = $type;
    }

    /**
     * Get the cooldown interval as a DateInterval object.
     */
    public function getCooldownInterval(): ?\DateInterval
    {
        if ($this->cooldownInterval === null) {
            return null;
        }

        return new \DateInterval($this->cooldownInterval);
    }

    /**
     * Set the cooldown interval.
     */
    public function setCooldownInterval(string $interval): void
    {
        try {
            new \DateInterval($interval);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(\sprintf(
                'Invalid cooldown interval format: "%s". Expected ISO 8601 duration format (e.g., PT1H, PT30M).',
                $interval
            ), 0, $e);
        }

        $this->cooldownInterval = $interval;
    }
}
