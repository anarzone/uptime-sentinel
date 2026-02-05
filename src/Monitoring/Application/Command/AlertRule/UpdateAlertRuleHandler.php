<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\AlertRule;

use App\Monitoring\Domain\Model\Notification\NotificationChannel;
use App\Monitoring\Domain\Repository\AlertRuleRepositoryInterface;
use App\Monitoring\Domain\Repository\NotificationChannelRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UpdateAlertRuleHandler
{
    public function __construct(
        private AlertRuleRepositoryInterface $alertRuleRepository,
        private NotificationChannelRepositoryInterface $notificationChannelRepository,
        private \App\Monitoring\Domain\Repository\MonitorRepositoryInterface $monitorRepository,
        private \App\Monitoring\Application\Service\MonitorAuthorizationService $authorizationService,
        private \Symfony\Bundle\SecurityBundle\Security $security,
    ) {
    }

    public function __invoke(UpdateAlertRuleCommand $command): void
    {
        $alertRule = $this->alertRuleRepository->find($command->id);

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
            \App\Monitoring\Domain\ValueObject\OwnerId::fromString($command->requesterId),
            $this->security->isGranted('ROLE_ADMIN')
        );

        // Update notification channel if target provided
        if ($command->target !== null) {
            $channel = $this->notificationChannelRepository->findByTarget($command->target);

            if ($channel === null) {
                $channel = NotificationChannel::fromDsn(
                    name: \sprintf('Channel for %s', $command->target),
                    dsn: $command->target,
                );
                $this->notificationChannelRepository->save($channel);
            }

            $alertRule->updateNotificationChannel($channel);
        }

        if ($command->failureThreshold !== null) {
            $alertRule->updateThreshold($command->failureThreshold);
        }

        if ($command->type !== null) {
            // Update notification type - NotificationType is an enum
            $alertRule->updateType(\App\Monitoring\Domain\Model\Alert\NotificationType::from($command->type));
        }

        if ($command->cooldownInterval !== null) {
            $alertRule->setCooldownInterval($command->cooldownInterval);
        }

        $this->alertRuleRepository->save($alertRule);
    }
}
