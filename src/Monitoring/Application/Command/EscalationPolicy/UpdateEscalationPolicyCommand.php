<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\EscalationPolicy;

final readonly class UpdateEscalationPolicyCommand
{
    public function __construct(
        public string $id,
        public int $level,
        public int $consecutiveFailures,
        public string $channelType,
        public string $target,
        public ?string $requesterId = null,
    ) {
    }
}
