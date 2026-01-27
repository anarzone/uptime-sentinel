<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Model\Notification;

use App\Monitoring\Domain\Model\Alert\AlertChannel;
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

    #[ORM\Column(type: Types::STRING, length: 20, enumType: AlertChannel::class)]
    public private(set) AlertChannel $type;

    #[ORM\Column(type: Types::TEXT)]
    public private(set) string $dsn;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    public private(set) bool $isEnabled = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public readonly \DateTimeImmutable $createdAt;

    public function __construct(
        NotificationChannelId $id,
        string $name,
        AlertChannel $type,
        string $dsn,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
        $this->dsn = $dsn;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function create(
        string $name,
        AlertChannel $type,
        string $dsn,
    ): self {
        return new self(
            id: NotificationChannelId::generate(),
            name: $name,
            type: $type,
            dsn: $dsn,
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

    public function update(string $name, AlertChannel $type, string $dsn): void
    {
        $this->name = $name;
        $this->type = $type;
        $this->dsn = $dsn;
    }
}
