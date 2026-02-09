<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\AlertRule;

use App\Monitoring\Application\Service\MonitorAuthorizationService;
use App\Monitoring\Domain\Model\Alert\AlertRule;
use App\Monitoring\Domain\Model\Alert\NotificationType;
use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Model\Notification\NotificationChannel;
use App\Monitoring\Domain\Model\Notification\NotificationChannelType;
use App\Monitoring\Domain\Repository\AlertRuleRepositoryInterface;
use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use App\Monitoring\Domain\Repository\NotificationChannelRepositoryInterface;
use App\Monitoring\Domain\ValueObject\OwnerId;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateAlertRuleHandler
{
    public function __construct(
        private AlertRuleRepositoryInterface $alertRuleRepository,
        private MonitorRepositoryInterface $monitorRepository,
        private NotificationChannelRepositoryInterface $channelRepository,
        private MonitorAuthorizationService $authorizationService,
        private Security $security,
    ) {
    }

    public function __invoke(CreateAlertRuleCommand $command): string
    {
        if ($command->requesterId === null) {
            throw new \InvalidArgumentException('requesterId is required');
        }

        $monitorId = MonitorId::fromString($command->monitorId);

        // Verify monitor exists
        $monitor = $this->monitorRepository->find($monitorId);
        if ($monitor === null) {
            throw new \InvalidArgumentException(\sprintf('Monitor with ID "%s" does not exist', $command->monitorId));
        }

        // Authorization check
        $this->authorizationService->requireOwnership(
            $monitor,
            OwnerId::fromString($command->requesterId),
            $this->security->isGranted('ROLE_ADMIN')
        );

        // Find or create notification channel
        $channelType = NotificationChannelType::from($command->channel);
        $ownerIdObj = $command->ownerId !== null ? OwnerId::fromString($command->ownerId) : null;
        $notificationChannel = $this->channelRepository->findByTypeAndTarget($channelType, $command->target, $ownerIdObj);

        if ($notificationChannel === null) {
            $notificationChannel = NotificationChannel::create(
                'Auto-created channel for '.$command->target,
                $channelType,
                $command->target,
                $ownerIdObj
            );
            $this->channelRepository->save($notificationChannel);
        }

        // Create alert rule
        $alertRule = AlertRule::create(
            $monitorId,
            $notificationChannel,
            $command->failureThreshold,
            NotificationType::from($command->type),
        );

        // Set cooldown if provided
        if ($command->cooldownInterval !== null) {
            $alertRule->setCooldownInterval($command->cooldownInterval);
        }

        $this->alertRuleRepository->save($alertRule);

        return $alertRule->id->toString();
    }
}
