<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\AlertRule;

use App\Monitoring\Application\Service\MonitorAuthorizationService;
use App\Monitoring\Domain\Model\Alert\NotificationType;
use App\Monitoring\Domain\Model\Notification\NotificationChannel;
use App\Monitoring\Domain\Repository\AlertRuleRepositoryInterface;
use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use App\Monitoring\Domain\Repository\NotificationChannelRepositoryInterface;
use App\Monitoring\Domain\ValueObject\OwnerId;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UpdateAlertRuleHandler
{
    public function __construct(
        private AlertRuleRepositoryInterface $alertRuleRepository,
        private NotificationChannelRepositoryInterface $notificationChannelRepository,
        private MonitorRepositoryInterface $monitorRepository,
        private MonitorAuthorizationService $authorizationService,
        private Security $security,
    ) {
    }

    public function __invoke(UpdateAlertRuleCommand $command): void
    {
        $alertRule = $this->alertRuleRepository->findById($command->id);

        if ($alertRule === null) {
            throw new \InvalidArgumentException(\sprintf('Alert rule with ID "%s" does not exist', $command->id));
        }

        $monitor = $this->monitorRepository->find($alertRule->monitorId);
        if ($monitor === null) {
            throw new \InvalidArgumentException('Associated monitor not found');
        }

        // Authorization check (Fix TOCTOU)
        if ($command->requesterId === null) {
            throw new \InvalidArgumentException('requesterId is required');
        }

        $this->authorizationService->requireOwnership(
            $monitor,
            OwnerId::fromString($command->requesterId),
            $this->security->isGranted('ROLE_ADMIN')
        );

        // Update notification channel if target provided
        if ($command->target !== null) {
            // If channelType is provided, find/create by type and target (explicit)
            if ($command->channelType !== null) {
                $type = \App\Monitoring\Domain\Model\Notification\NotificationChannelType::from($command->channelType);
                $channel = $this->notificationChannelRepository->findByTypeAndTarget($type, $command->target);

                if ($channel === null) {
                    $channel = NotificationChannel::create(
                        name: $command->channelType.' - '.$command->target,
                        type: $type,
                        dsn: $command->target,
                        ownerId: OwnerId::fromString($command->requesterId)
                    );
                    $this->notificationChannelRepository->save($channel);
                }
            } else {
                // Legacy/Implicit mode: infer from DSN
                $channel = $this->notificationChannelRepository->findByTarget($command->target);
                if ($channel === null) {
                    $channel = NotificationChannel::fromDsn(
                        name: \sprintf('Channel for %s', $command->target),
                        dsn: $command->target,
                    );
                    $this->notificationChannelRepository->save($channel);
                }
            }

            $alertRule->updateNotificationChannel($channel);
        }

        if ($command->failureThreshold !== null) {
            $alertRule->updateThreshold($command->failureThreshold);
        }

        if ($command->type !== null) {
            // Update notification type - NotificationType is an enum
            $alertRule->updateType(NotificationType::from($command->type));
        }

        if ($command->cooldownInterval !== null) {
            $alertRule->setCooldownInterval($command->cooldownInterval);
        }

        $this->alertRuleRepository->save($alertRule);
    }
}
