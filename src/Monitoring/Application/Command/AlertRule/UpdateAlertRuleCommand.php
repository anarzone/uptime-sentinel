<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\AlertRule;

final readonly class UpdateAlertRuleCommand
{
    public function __construct(
        public string $id,
        public ?string $target,
        public ?int $failureThreshold,
        public ?string $type,
        public ?string $cooldownInterval,
    ) {
    }
}
