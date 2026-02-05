<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Twig\Components;

use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class MonitorList
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public int $page = 1;

    public int $limit = 20;

    public function __construct(
        private readonly MonitorRepositoryInterface $monitorRepository
    ) {
    }

    public function getMonitors(): array
    {
        return $this->monitorRepository->findPaginated($this->page, $this->limit);
    }

    public function getTotalCount(): int
    {
        return $this->monitorRepository->countTotal();
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
}
