<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Dto;

use App\Monitoring\Domain\Model\Alert\EscalationPolicy;

final readonly class EscalationPolicyResponseDto
{
    public function __construct(
        public string $id,
        public ?string $monitorId,
        public int $level,
        public int $consecutiveFailures,
        public string $channel,
        public string $target,
        public bool $isEnabled,
        public string $createdAt,
    ) {
    }

    public static function fromEntity(EscalationPolicy $policy): self
    {
        return new self(
            $policy->id->toString(),
            $policy->monitorId?->toString(),
            $policy->level,
            $policy->consecutiveFailures,
            $policy->channel->value,
            $policy->target,
            $policy->isEnabled,
            $policy->createdAt->format(\DateTimeInterface::ATOM),
        );
    }

    /**
     * @param array<EscalationPolicy> $policies
     *
     * @return array<self>
     */
    public static function fromEntities(array $policies): array
    {
        return array_map(fn (EscalationPolicy $policy) => self::fromEntity($policy), $policies);
    }
}
