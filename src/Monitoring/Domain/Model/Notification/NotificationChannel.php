<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Model\Notification;

use App\Monitoring\Domain\ValueObject\OwnerId;
use App\Monitoring\Infrastructure\Persistence\NotificationChannelRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationChannelRepository::class)]
#[ORM\Table(name: 'notification_channels')]
final class NotificationChannel
{
    #[ORM\Embedded(class: NotificationChannelId::class, columnPrefix: false)]
    public readonly NotificationChannelId $id;

    #[ORM\Column(type: Types::STRING, length: 255)]
    public private(set) string $name;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: NotificationChannelType::class)]
    public private(set) NotificationChannelType $type;

    #[ORM\Column(type: Types::TEXT)]
    public private(set) string $dsn;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    public private(set) bool $isEnabled = true;

    #[ORM\Column(type: Types::STRING, length: 36, nullable: true)]
    public private(set) ?string $ownerId;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public readonly \DateTimeImmutable $createdAt;

    public function __construct(
        NotificationChannelId $id,
        string $name,
        NotificationChannelType $type,
        string $dsn,
        ?OwnerId $ownerId = null,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
        $this->dsn = $dsn;
        $this->ownerId = $ownerId?->value;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function create(
        string $name,
        NotificationChannelType $type,
        string $dsn,
        ?OwnerId $ownerId = null,
    ): self {
        return new self(
            id: NotificationChannelId::generate(),
            name: $name,
            type: $type,
            dsn: $dsn,
            ownerId: $ownerId,
        );
    }

    public static function fromDsn(
        string $name,
        string $dsn,
    ): self {
        return new self(
            id: NotificationChannelId::generate(),
            name: $name,
            type: self::inferTypeFromDsn($dsn),
            dsn: $dsn,
        );
    }

    private static function inferTypeFromDsn(string $dsn): NotificationChannelType
    {
        // Email DSN format: mailto://user@example.com or user@example.com
        if (str_contains($dsn, '@') || str_starts_with($dsn, 'mailto:')) {
            return NotificationChannelType::EMAIL;
        }

        // Slack DSN format: slack://webhook-url or contains slack
        if (str_starts_with($dsn, 'slack://') || str_contains($dsn, 'slack.com')) {
            return NotificationChannelType::SLACK;
        }

        // Webhook DSN format: https:// or http://
        if (str_starts_with($dsn, 'https://') || str_starts_with($dsn, 'http://')) {
            return NotificationChannelType::WEBHOOK;
        }

        throw new \InvalidArgumentException(\sprintf(
            'Cannot infer notification channel type from DSN: "%s". '.
            'Supported formats: email (user@example.com), slack (slack://...), webhook (https://...)',
            $dsn
        ));
    }

    public function enable(): void
    {
        $this->isEnabled = true;
    }

    public function disable(): void
    {
        $this->isEnabled = false;
    }

    public function update(string $name, NotificationChannelType $type, string $dsn): void
    {
        $this->name = $name;
        $this->type = $type;
        $this->dsn = $dsn;
    }
}
