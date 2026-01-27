<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\EscalationPolicy;

use App\Monitoring\Domain\Model\Alert\EscalationPolicy;
use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Repository\EscalationPolicyRepositoryInterface;
use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateEscalationPolicyHandler
{
    public function __construct(
        private EscalationPolicyRepositoryInterface $escalationPolicyRepository,
        private MonitorRepositoryInterface $monitorRepository,
    ) {
    }

    public function __invoke(CreateEscalationPolicyCommand $command): string
    {
        // If monitor-specific, verify monitor exists
        if ($command->monitorId !== null) {
            $monitorId = MonitorId::fromString($command->monitorId);
            $monitor = $this->monitorRepository->find($monitorId);

            if ($monitor === null) {
                throw new \InvalidArgumentException(\sprintf('Monitor with ID "%s" does not exist', $command->monitorId));
            }
        }

        $policy = EscalationPolicy::create(
            $command->monitorId !== null ? MonitorId::fromString($command->monitorId) : null,
            $command->level,
            $command->consecutiveFailures,
            \App\Monitoring\Domain\Model\Alert\AlertChannel::from($command->channel),
            $command->target,
        );

        $this->escalationPolicyRepository->save($policy);

        return $policy->id->toString();
    }
}
