<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Dto;

use App\Monitoring\Domain\Model\Alert\NotificationType;
use App\Monitoring\Domain\Model\Notification\NotificationChannelType;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateAlertRuleRequestDto
{
    public const ALLOWED_CHANNELS = ['email', 'slack', 'webhook'];
    public const ALLOWED_TYPES = ['failure', 'recovery', 'both'];

    public function __construct(
        #[Assert\NotBlank(message: 'Monitor ID is required')]
        public string $monitorId,

        #[Assert\Choice(choices: self::ALLOWED_CHANNELS, message: 'Invalid channel')]
        public ?string $channel = null,

        #[Assert\Length(max: 255)]
        public ?string $target = null,

        #[Assert\Type('int')]
        #[Assert\Range(min: 1, max: 100)]
        public ?int $failureThreshold = null,

        #[Assert\Choice(choices: self::ALLOWED_TYPES, message: 'Invalid notification type')]
        public ?string $type = null,

        #[Assert\Length(max: 32)]
        public ?string $cooldownInterval = null,
    ) {
    }

    public function getChannel(): ?NotificationChannelType
    {
        return $this->channel !== null ? NotificationChannelType::from($this->channel) : null;
    }

    public function getType(): ?NotificationType
    {
        return $this->type !== null ? NotificationType::from($this->type) : null;
    }
}
