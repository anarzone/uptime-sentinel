<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class BatchCreateAlertRuleRequestDto
{
    /**
     * @param string[] $monitorIds
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Monitor IDs are required')]
        #[Assert\Type('array')]
        #[Assert\Count(min: 1, minMessage: 'At least one Monitor ID is required')]
        #[Assert\All([new Assert\Uuid(message: 'Invalid Monitor ID format')])]
        public array $monitorIds,

        #[Assert\NotNull(message: 'Channel is required')]
        #[Assert\Choice(choices: CreateAlertRuleRequestDto::ALLOWED_CHANNELS, message: 'Invalid channel')]
        public string $channel,

        #[Assert\NotBlank(message: 'Target is required')]
        #[Assert\Length(max: 255)]
        public string $target,

        #[Assert\NotNull(message: 'Failure threshold is required')]
        #[Assert\Type('int')]
        #[Assert\Range(min: 1, max: 100)]
        public int $failureThreshold = 3,

        #[Assert\Choice(choices: CreateAlertRuleRequestDto::ALLOWED_TYPES, message: 'Invalid notification type')]
        public string $type = 'failure',

        #[Assert\Length(max: 32)]
        public ?string $cooldownInterval = null,
    ) {
    }
}
