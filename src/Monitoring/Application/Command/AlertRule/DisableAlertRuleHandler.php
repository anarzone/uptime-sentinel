<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\AlertRule;

use App\Monitoring\Domain\Repository\AlertRuleRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class DisableAlertRuleHandler
{
    public function __construct(
        private AlertRuleRepositoryInterface $alertRuleRepository,
    ) {
    }

    public function __invoke(DisableAlertRuleCommand $command): void
    {
        $alertRule = $this->alertRuleRepository->find($command->id);

        if ($alertRule === null) {
            throw new \InvalidArgumentException(\sprintf('Alert rule with ID "%s" does not exist', $command->id));
        }

        $alertRule->disable();
        $this->alertRuleRepository->save($alertRule);
    }
}
