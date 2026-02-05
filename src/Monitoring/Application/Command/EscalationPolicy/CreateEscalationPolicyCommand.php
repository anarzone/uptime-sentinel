<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\EscalationPolicy;

final readonly class CreateEscalationPolicyCommand
{
    public function __construct(
        public string $id,
        public string $monitorId,
        public int $level,
        public int $consecutiveFailures,
        public string $channel,
        public string $target,
        public ?string $requesterId = null,
    ) {
    }
}
