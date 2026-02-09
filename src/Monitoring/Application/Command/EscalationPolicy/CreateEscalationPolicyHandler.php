<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\EscalationPolicy;

use App\Monitoring\Domain\Model\Alert\EscalationPolicy;
use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Model\Notification\NotificationChannelType;
use App\Monitoring\Domain\Repository\EscalationPolicyRepositoryInterface;
use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use App\Monitoring\Domain\Repository\NotificationChannelRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateEscalationPolicyHandler
{
    public function __construct(
        private MonitorRepositoryInterface $monitorRepository,
        private NotificationChannelRepositoryInterface $notificationChannelRepository,
        private EscalationPolicyRepositoryInterface $escalationPolicyRepository,
        private \App\Monitoring\Application\Service\MonitorAuthorizationService $authorizationService,
        private \Symfony\Bundle\SecurityBundle\Security $security,
    ) {
    }

    public function __invoke(CreateEscalationPolicyCommand $command): void
    {
        if ($command->requesterId === null) {
            throw new \InvalidArgumentException('requesterId is required');
        }

        // Validate monitor exists
        $monitorId = MonitorId::fromString($command->monitorId);
        $monitor = $this->monitorRepository->findById($monitorId);

        if ($monitor === null) {
            throw new \InvalidArgumentException(\sprintf(
                'Monitor with ID "%s" does not exist',
                $command->monitorId
            ));
        }

        // Authorization check
        $this->authorizationService->requireOwnership(
            $monitor,
            \App\Monitoring\Domain\ValueObject\OwnerId::fromString($command->requesterId),
            $this->security->isGranted('ROLE_ADMIN')
        );

        // Find notification channel by type and target
        $channelType = NotificationChannelType::from($command->channel);
        $notificationChannel = $this->notificationChannelRepository->findByTypeAndTarget(
            $channelType,
            $command->target
        );

        if ($notificationChannel === null) {
            $ownerId = \App\Monitoring\Domain\ValueObject\OwnerId::fromString($command->requesterId);
            $notificationChannel = \App\Monitoring\Domain\Model\Notification\NotificationChannel::create(
                name: $command->channel.' - '.$command->target,
                type: $channelType,
                dsn: $command->target,
                ownerId: $ownerId
            );
            $this->notificationChannelRepository->save($notificationChannel);
        }

        // Create escalation policy
        $policy = EscalationPolicy::create(
            $monitorId,
            $command->level,
            $command->consecutiveFailures,
            $notificationChannel,
        );

        $this->escalationPolicyRepository->save($policy);
    }
}
