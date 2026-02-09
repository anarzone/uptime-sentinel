<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class BatchCreateEscalationPolicyRequestDto
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

        #[Assert\NotNull(message: 'Level is required')]
        #[Assert\Type('int')]
        #[Assert\Range(min: 1, max: 10)]
        public int $level,

        #[Assert\NotNull(message: 'Consecutive failures is required')]
        #[Assert\Type('int')]
        #[Assert\Range(min: 1, max: 1000)]
        public int $consecutiveFailures,

        #[Assert\NotNull(message: 'Channel is required')]
        #[Assert\Choice(choices: CreateEscalationPolicyRequestDto::ALLOWED_CHANNELS, message: 'Invalid channel')]
        public string $channel,

        #[Assert\NotBlank(message: 'Target is required')]
        #[Assert\Length(max: 255)]
        public string $target,
    ) {
    }
}
