<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\EscalationPolicy;

use Symfony\Component\Uid\UuidV7;

final readonly class CreateEscalationPolicyCommand
{
    public function __construct(
        public string $id,
        public ?string $monitorId,
        public int $level,
        public int $consecutiveFailures,
        public string $channel,
        public string $target,
    ) {
    }

    public static function create(
        ?string $monitorId,
        int $level,
        int $consecutiveFailures,
        string $channel,
        string $target,
    ): self {
        return new self(
            id: new UuidV7()->toRfc4122(),
            monitorId: $monitorId,
            level: $level,
            consecutiveFailures: $consecutiveFailures,
            channel: $channel,
            target: $target,
        );
    }
}
