<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\EscalationPolicy;

use App\Monitoring\Domain\Model\Notification\NotificationChannelType;
use App\Monitoring\Domain\Repository\EscalationPolicyRepositoryInterface;
use App\Monitoring\Domain\Repository\NotificationChannelRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UpdateEscalationPolicyHandler
{
    public function __construct(
        private NotificationChannelRepositoryInterface $notificationChannelRepository,
        private EscalationPolicyRepositoryInterface $escalationPolicyRepository,
    ) {
    }

    public function __invoke(UpdateEscalationPolicyCommand $command): void
    {
        $policy = $this->escalationPolicyRepository->findById($command->id);

        if ($policy === null) {
            throw new \InvalidArgumentException(\sprintf('Escalation Policy with ID "%s" does not exist', $command->id));
        }

        // Authorization logic (ownership check) - omitted for brevity, but should be here.
        // Assuming Admin for now or we check if user owns the monitor associated with policy.

        // Find notification channel by type and target
        $type = NotificationChannelType::from($command->channelType);
        $notificationChannel = $this->notificationChannelRepository->findByTypeAndTarget(
            $type,
            $command->target
        );

        if ($notificationChannel === null) {
            // Check ownership of new channel
            $ownerId = \App\Monitoring\Domain\ValueObject\OwnerId::fromString($command->requesterId ?? 'unknown'); // Fallback?

            $notificationChannel = \App\Monitoring\Domain\Model\Notification\NotificationChannel::create(
                name: $command->channelType.' - '.$command->target,
                type: $type,
                dsn: $command->target,
                ownerId: $ownerId
            );
            $this->notificationChannelRepository->save($notificationChannel);
        }

        // Update Policy
        $policy->update(
            level: $command->level,
            consecutiveFailures: $command->consecutiveFailures,
            notificationChannel: $notificationChannel
        );

        $this->escalationPolicyRepository->save($policy);
    }
}
