<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Dto;

use App\Monitoring\Domain\Model\Alert\AlertChannel;
use App\Monitoring\Domain\Model\Alert\NotificationType;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateAlertRuleRequestDto
{
    public const ALLOWED_CHANNELS = ['email', 'slack', 'webhook'];
    public const ALLOWED_TYPES = ['failure', 'recovery', 'both'];

    public function __construct(
        #[Assert\NotBlank(message: 'Monitor ID is required')]
        public string $monitorId,

        #[Assert\NotNull(message: 'Channel is required')]
        #[Assert\Choice(choices: self::ALLOWED_CHANNELS, message: 'Invalid channel')]
        public string $channel,

        #[Assert\NotBlank(message: 'Target is required')]
        #[Assert\Length(max: 255)]
        public string $target,

        #[Assert\NotNull(message: 'Failure threshold is required')]
        #[Assert\Type('int')]
        #[Assert\Range(min: 1, max: 100)]
        public int $failureThreshold = 3,

        #[Assert\Choice(choices: self::ALLOWED_TYPES, message: 'Invalid notification type')]
        public string $type = 'failure',

        #[Assert\Length(max: 32)]
        public ?string $cooldownInterval = null,
    ) {
    }

    public function getChannel(): AlertChannel
    {
        return AlertChannel::from($this->channel);
    }

    public function getType(): NotificationType
    {
        return NotificationType::from($this->type);
    }
}
