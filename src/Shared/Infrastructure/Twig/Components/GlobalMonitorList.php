<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Twig\Components;

use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class GlobalMonitorList
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public int $page = 1;

    public int $limit = 20;

    public function __construct(
        private readonly MonitorRepositoryInterface $monitorRepository,
        private readonly \Symfony\Bundle\SecurityBundle\Security $security,
        private readonly \Symfony\Component\Messenger\MessageBusInterface $bus
    ) {
    }

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

    public function getMonitors(): array
    {
        return $this->monitorRepository->findPaginated($this->page, $this->limit, $this->getOwnerId());
    }

    public function getTotalCount(): int
    {
        return $this->monitorRepository->countTotal($this->getOwnerId());
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

    #[LiveAction]
    public function delete(#[LiveArg] string $id): void
    {
        $user = $this->security->getUser();
        $requesterId = $user instanceof \App\Security\Domain\Entity\User ? $user->getId()->toRfc4122() : $user?->getUserIdentifier();

        // Monitor delete uses string id normally?
        // Let's check DeleteMonitorCommand

        $command = new \App\Monitoring\Application\Command\DeleteMonitor\DeleteMonitorCommand(
            uuid: $id,
            requesterId: $requesterId
        );

        $this->bus->dispatch($command);
    }
}
