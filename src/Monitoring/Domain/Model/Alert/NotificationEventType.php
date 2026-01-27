<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Model\Alert;

enum NotificationEventType: string
{
    case FAILURE = 'failure';
    case RECOVERY = 'recovery';
    case ESCALATION = 'escalation';
}
