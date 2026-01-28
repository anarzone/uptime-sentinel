<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Model\Notification;

enum NotificationChannelType: string
{
    case EMAIL = 'email';
    case SLACK = 'slack';
    case WEBHOOK = 'webhook';
}
