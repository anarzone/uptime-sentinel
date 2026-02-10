<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Twig\Components;

use App\Monitoring\Domain\Repository\EscalationPolicyRepositoryInterface;
use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class GlobalEscalationPolicyList
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public int $page = 1;

    public int $limit = 20;

    public function __construct(
        private readonly EscalationPolicyRepositoryInterface $policyRepository,
        private readonly MonitorRepositoryInterface $monitorRepository,
        private readonly \Symfony\Bundle\SecurityBundle\Security $security,
        private readonly \Symfony\Component\Messenger\MessageBusInterface $bus
    ) {
    }

    #[LiveProp]
    public ?string $monitorId = null;

    private function getOwnerId(): ?\App\Monitoring\Domain\ValueObject\OwnerId
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return null;
        }

        $user = $this->security->getUser();
        if (!$user instanceof \App\Security\Domain\Entity\User) {
            $identifier = $user?->getUserIdentifier();

            return $identifier !== null ? \App\Monitoring\Domain\ValueObject\OwnerId::fromString($identifier) : null;
        }

        return \App\Monitoring\Domain\ValueObject\OwnerId::fromString($user->getId()->toRfc4122());
    }

    public function getPolicies(): array
    {
        if ($this->monitorId !== null) {
            return $this->policyRepository->findAll(\App\Monitoring\Domain\Model\Monitor\MonitorId::fromString($this->monitorId));
        }

        return $this->policyRepository->findPaginated($this->page, $this->limit, $this->getOwnerId());
    }

    public function getTotalCount(): int
    {
        return $this->policyRepository->countTotal($this->getOwnerId());
    }

    public function getTotalPages(): int
    {
        return (int) ceil($this->getTotalCount() / $this->limit);
    }

    #[LiveAction]
    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    public function getMonitorsForPolicies(array $policies): array
    {
        $monitorIds = array_unique(array_map(fn ($policy) => $policy->monitorId->value, $policies));
        $monitors = $this->monitorRepository->findByIds($monitorIds);
        $monitorMap = [];
        foreach ($monitors as $monitor) {
            $monitorMap[$monitor->id->value] = $monitor;
        }

        return $monitorMap;
    }

    #[LiveAction]
    public function delete(#[LiveArg] string $id): void
    {
        $user = $this->security->getUser();
        $requesterId = $user instanceof \App\Security\Domain\Entity\User ? $user->getId()->toRfc4122() : $user?->getUserIdentifier();

        $command = new \App\Monitoring\Application\Command\EscalationPolicy\DeleteEscalationPolicyCommand(
            id: $id,
            requesterId: $requesterId
        );

        $this->bus->dispatch($command);
    }
}
