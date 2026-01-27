<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\EscalationPolicy;

use App\Monitoring\Domain\Repository\EscalationPolicyRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class EnableEscalationPolicyHandler
{
    public function __construct(
        private EscalationPolicyRepositoryInterface $escalationPolicyRepository,
    ) {
    }

    public function __invoke(EnableEscalationPolicyCommand $command): void
    {
        $policy = $this->escalationPolicyRepository->find($command->id);

        if ($policy === null) {
            throw new \InvalidArgumentException(\sprintf('Escalation policy with ID "%s" does not exist', $command->id));
        }

        $policy->enable();
        $this->escalationPolicyRepository->save($policy);
    }
}
