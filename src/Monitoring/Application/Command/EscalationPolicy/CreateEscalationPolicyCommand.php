<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\EscalationPolicy;

use Symfony\Component\Uid\UuidV7;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateEscalationPolicyCommand
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $id,
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $monitorId,
        #[Assert\Positive]
        public int $level,
        #[Assert\Positive]
        public int $consecutiveFailures,
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['email', 'slack', 'webhook'])]
        public string $channel,
        #[Assert\NotBlank]
        public string $target,
    ) {
    }

    public static function create(
        string $monitorId,
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
