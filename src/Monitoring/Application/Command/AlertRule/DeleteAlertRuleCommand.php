<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\AlertRule;

final readonly class DeleteAlertRuleCommand
{
    public function __construct(
        public string $id,
        public ?string $requesterId = null,
    ) {
    }
}
