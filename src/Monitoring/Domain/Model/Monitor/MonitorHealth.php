<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Model\Monitor;

enum MonitorHealth: string
{
    case UP = 'up';
    case DOWN = 'down';
    case UNKNOWN = 'unknown';
}
