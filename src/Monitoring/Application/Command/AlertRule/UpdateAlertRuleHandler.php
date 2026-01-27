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

        // Update fields if provided
        if ($command->target !== null) {
            $alertRule->updateTarget($command->target);
        }

        if ($command->failureThreshold !== null) {
            $alertRule->updateThreshold($command->failureThreshold);
        }

        if ($command->type !== null) {
            match ($command->type) {
                'failure' => $alertRule->disableRecoveryNotifications(),
                'recovery' => $alertRule->setRecoveryOnly(),
                'both' => $alertRule->enableRecoveryNotifications(),
                default => throw new \InvalidArgumentException(\sprintf('Invalid notification type "%s"', $command->type)),
            };
        }

        if ($command->cooldownInterval !== null) {
            $alertRule->setCooldownInterval($command->cooldownInterval);
        }

        $this->alertRuleRepository->save($alertRule);
    }
}
