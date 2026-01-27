<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\AlertRule;

use Symfony\Component\Uid\UuidV7;

final readonly class CreateAlertRuleCommand
{
    public function __construct(
        public string $id,
        public string $monitorId,
        public string $channel,
        public string $target,
        public int $failureThreshold,
        public string $type,
        public ?string $cooldownInterval,
    ) {
    }

    public static function create(
        string $monitorId,
        string $channel,
        string $target,
        int $failureThreshold,
        string $type,
        ?string $cooldownInterval,
    ): self {
        return new self(
            id: (new UuidV7())->toRfc4122(),
            monitorId: $monitorId,
            channel: $channel,
            target: $target,
            failureThreshold: $failureThreshold,
            type: $type,
            cooldownInterval: $cooldownInterval,
        );
    }
}
