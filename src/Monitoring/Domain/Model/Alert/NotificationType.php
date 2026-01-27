<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Model\Alert;

enum NotificationType: string
{
    case FAILURE = 'failure';     // Only send on DOWN
    case RECOVERY = 'recovery';   // Only send on UP
    case BOTH = 'both';           // Send on both
}
