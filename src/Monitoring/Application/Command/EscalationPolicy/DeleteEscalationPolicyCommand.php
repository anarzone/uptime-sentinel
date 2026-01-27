<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\EscalationPolicy;

final readonly class DeleteEscalationPolicyCommand
{
    public function __construct(
        public string $id,
    ) {
    }
}
