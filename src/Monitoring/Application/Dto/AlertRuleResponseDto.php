<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Dto;

use App\Monitoring\Domain\Model\Alert\AlertRule;

final readonly class AlertRuleResponseDto
{
    public function __construct(
        public string $id,
        public string $monitorId,
        public string $channel,
        public string $type,
        public string $target,
        public int $failureThreshold,
        public ?string $cooldownInterval,
        public bool $isEnabled,
        public string $createdAt,
    ) {
    }

    public static function fromEntity(AlertRule $rule): self
    {
        return new self(
            $rule->id->toString(),
            $rule->monitorId->toString(),
            $rule->notificationChannel->type->value,
            $rule->type->value,
            $rule->notificationChannel->dsn,
            $rule->failureThreshold,
            $rule->cooldownInterval,
            $rule->isEnabled,
            $rule->createdAt->format(\DateTimeInterface::ATOM),
        );
    }

    /**
     * @param array<AlertRule> $rules
     *
     * @return array<self>
     */
    public static function fromEntities(array $rules): array
    {
        return array_map(fn (AlertRule $rule) => self::fromEntity($rule), $rules);
    }
}
