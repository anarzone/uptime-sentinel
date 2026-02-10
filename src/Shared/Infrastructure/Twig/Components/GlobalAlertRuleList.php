<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Twig\Components;

use App\Monitoring\Domain\Repository\AlertRuleRepositoryInterface;
use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class GlobalAlertRuleList
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public int $page = 1;

    public int $limit = 20;

    public function __construct(
        private readonly AlertRuleRepositoryInterface $alertRuleRepository,
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

    public function getAlertRules(): array
    {
        if ($this->monitorId !== null) {
            return $this->alertRuleRepository->findByMonitorId(\App\Monitoring\Domain\Model\Monitor\MonitorId::fromString($this->monitorId));
        }

        return $this->alertRuleRepository->findPaginated($this->page, $this->limit, $this->getOwnerId());
    }

    public function getTotalCount(): int
    {
        return $this->alertRuleRepository->countTotal($this->getOwnerId());
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

    // Helper to get monitor info since we didn't join it in repository efficiently (we only joined for filtering)
    // Actually we can join it in repository to eager load.
    // Let's rely on AlertRule -> monitorId.
    // If we want to show Monitor Name, we need to fetch it.
    // Since AlertRule doesn't have Monitor association, we can't easily access rule.monitor.name.
    // We should probably fetch monitors by IDs for the current page of rules.
    public function getMonitorsForRules(array $rules): array
    {
        $monitorIds = array_unique(array_map(fn ($rule) => $rule->monitorId->value, $rules));
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

        $command = new \App\Monitoring\Application\Command\AlertRule\DeleteAlertRuleCommand(
            id: $id,
            requesterId: $requesterId
        );

        $this->bus->dispatch($command);
    }
}
