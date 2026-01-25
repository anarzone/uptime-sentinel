<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Model\Alert;

enum AlertChannel: string
{
    case EMAIL = 'email';
    case SLACK = 'slack';
    case WEBHOOK = 'webhook';
}
