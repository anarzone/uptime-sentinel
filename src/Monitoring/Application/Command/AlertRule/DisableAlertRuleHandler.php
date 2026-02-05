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
        private \App\Monitoring\Domain\Repository\MonitorRepositoryInterface $monitorRepository,
        private \App\Monitoring\Application\Service\MonitorAuthorizationService $authorizationService,
        private \Symfony\Bundle\SecurityBundle\Security $security,
    ) {
    }

    public function __invoke(DisableAlertRuleCommand $command): void
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

        $alertRule->disable();
        $this->alertRuleRepository->save($alertRule);
    }
}
