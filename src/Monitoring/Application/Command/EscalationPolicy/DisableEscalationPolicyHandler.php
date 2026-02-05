<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\EscalationPolicy;

use App\Monitoring\Domain\Repository\EscalationPolicyRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class DisableEscalationPolicyHandler
{
    public function __construct(
        private EscalationPolicyRepositoryInterface $escalationPolicyRepository,
        private \App\Monitoring\Domain\Repository\MonitorRepositoryInterface $monitorRepository,
        private \App\Monitoring\Application\Service\MonitorAuthorizationService $authorizationService,
        private \Symfony\Bundle\SecurityBundle\Security $security,
    ) {
    }

    public function __invoke(DisableEscalationPolicyCommand $command): void
    {
        if ($command->requesterId === null) {
            throw new \InvalidArgumentException('requesterId is required');
        }

        $policy = $this->escalationPolicyRepository->find($command->id);

        if ($policy === null) {
            throw new \InvalidArgumentException(\sprintf('Escalation policy with ID "%s" does not exist', $command->id));
        }

        $monitor = $this->monitorRepository->find($policy->monitorId);
        if ($monitor === null) {
            throw new \InvalidArgumentException('Associated monitor not found');
        }

        // Authorization check
        $this->authorizationService->requireOwnership(
            $monitor,
            \App\Monitoring\Domain\ValueObject\OwnerId::fromString($command->requesterId),
            $this->security->isGranted('ROLE_ADMIN')
        );

        $policy->disable();
        $this->escalationPolicyRepository->save($policy);
    }
}
