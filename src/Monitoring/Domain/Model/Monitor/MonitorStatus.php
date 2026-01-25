<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Model\Monitor;

enum MonitorStatus: string
{
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case DISABLED = 'disabled';
}
