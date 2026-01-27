<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\AlertRule;

use App\Monitoring\Domain\Model\Alert\AlertRule;
use App\Monitoring\Domain\Model\Alert\NotificationType;
use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Repository\AlertRuleRepositoryInterface;
use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateAlertRuleHandler
{
    public function __construct(
        private AlertRuleRepositoryInterface $alertRuleRepository,
        private MonitorRepositoryInterface $monitorRepository,
        private \App\Monitoring\Domain\Repository\NotificationChannelRepositoryInterface $channelRepository,
    ) {
    }

    public function __invoke(CreateAlertRuleCommand $command): string
    {
        $monitorId = MonitorId::fromString($command->monitorId);

        // Verify monitor exists
        $monitor = $this->monitorRepository->find($monitorId);
        if ($monitor === null) {
            throw new \InvalidArgumentException(\sprintf('Monitor with ID "%s" does not exist', $command->monitorId));
        }

        // Find or create notification channel
        $channelType = \App\Monitoring\Domain\Model\Alert\AlertChannel::from($command->channel);
        $notificationChannel = $this->channelRepository->findByTypeAndTarget($channelType, $command->target);

        if ($notificationChannel === null) {
            $notificationChannel = \App\Monitoring\Domain\Model\Notification\NotificationChannel::create(
                'Auto-created channel for '.$command->target,
                $channelType,
                $command->target
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
