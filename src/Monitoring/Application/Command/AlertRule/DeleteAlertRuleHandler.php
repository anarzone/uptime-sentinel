<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\AlertRule;

use App\Monitoring\Application\Service\MonitorAuthorizationService;
use App\Monitoring\Domain\Repository\AlertRuleRepositoryInterface;
use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use App\Monitoring\Domain\ValueObject\OwnerId;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class DeleteAlertRuleHandler
{
    public function __construct(
        private AlertRuleRepositoryInterface $alertRuleRepository,
        private MonitorRepositoryInterface $monitorRepository,
        private MonitorAuthorizationService $authorizationService,
        private Security $security,
    ) {
    }

    public function __invoke(DeleteAlertRuleCommand $command): void
    {
        if ($command->requesterId === null) {
            throw new \InvalidArgumentException('requesterId is required');
        }

        $alertRule = $this->alertRuleRepository->find($command->id);

        if ($alertRule === null) {
            throw new \InvalidArgumentException(\sprintf('Alert rule with ID "%s" does not exist', $command->id));
        }

        $monitor = $this->monitorRepository->find($alertRule->monitorId);
        if ($monitor === null) {
            throw new \InvalidArgumentException('Associated monitor not found');
        }

        // Authorization check (Fix TOCTOU)
        $this->authorizationService->requireOwnership(
            $monitor,
            OwnerId::fromString($command->requesterId),
            $this->security->isGranted('ROLE_ADMIN')
        );

        $this->alertRuleRepository->remove($alertRule);
    }
}
