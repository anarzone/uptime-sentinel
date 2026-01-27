<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\AlertRule;

use App\Monitoring\Domain\Repository\AlertRuleRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UpdateAlertRuleHandler
{
    public function __construct(
        private AlertRuleRepositoryInterface $alertRuleRepository,
    ) {
    }

    public function __invoke(UpdateAlertRuleCommand $command): void
    {
        $alertRule = $this->alertRuleRepository->find($command->id);

        if ($alertRule === null) {
            throw new \InvalidArgumentException(\sprintf('Alert rule with ID "%s" does not exist', $command->id));
        }

        // Update notification channel if target provided
        // TODO: Implement notification channel lookup and update
        // For now, skip target updates as it requires NotificationChannelRepository

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
