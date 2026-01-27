<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Repository;

use App\Monitoring\Domain\Model\Alert\AlertChannel;
use App\Monitoring\Domain\Model\Notification\NotificationChannel;
use App\Monitoring\Domain\Model\Notification\NotificationChannelId;

interface NotificationChannelRepositoryInterface
{
    public function findNotificationChannel(NotificationChannelId $id): ?NotificationChannel;

    public function findByTarget(string $target): ?NotificationChannel;

    public function findByTypeAndTarget(AlertChannel $type, string $target): ?NotificationChannel;

    public function save(NotificationChannel $channel): void;

    public function remove(NotificationChannel $channel): void;

    /**
     * @return NotificationChannel[]
     */
    public function findAll(): array;
}
