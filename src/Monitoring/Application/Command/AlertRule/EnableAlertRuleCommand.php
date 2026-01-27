<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\AlertRule;

final readonly class EnableAlertRuleCommand
{
    public function __construct(
        public string $id,
    ) {
    }
}
