<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Dto;

use App\Monitoring\Domain\Model\Notification\NotificationChannelType;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateEscalationPolicyRequestDto
{
    public const array ALLOWED_CHANNELS = ['email', 'slack', 'webhook'];

    public function __construct(
        public ?string $monitorId, // null for global policies

        #[Assert\NotNull(message: 'Level is required')]
        #[Assert\Type('int')]
        #[Assert\Range(min: 1, max: 10)]
        public int $level,

        #[Assert\NotNull(message: 'Consecutive failures is required')]
        #[Assert\Type('int')]
        #[Assert\Range(min: 1, max: 1000)]
        public int $consecutiveFailures,

        #[Assert\NotNull(message: 'Channel is required')]
        #[Assert\Choice(choices: self::ALLOWED_CHANNELS, message: 'Invalid channel')]
        public string $channel,

        #[Assert\NotBlank(message: 'Target is required')]
        #[Assert\Length(max: 255)]
        public string $target,
    ) {
    }

    public function getChannel(): NotificationChannelType
    {
        return NotificationChannelType::from($this->channel);
    }

    public function getMonitorId(): ?\App\Monitoring\Domain\Model\Monitor\MonitorId
    {
        return $this->monitorId !== null
            ? \App\Monitoring\Domain\Model\Monitor\MonitorId::fromString($this->monitorId)
            : null;
    }
}
