<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Repository;

use App\Monitoring\Domain\Model\Monitor\Monitor;
use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Model\Monitor\MonitorStatus;

/**
 * @method Monitor|null find(MonitorId $monitorId)
 */
interface MonitorRepositoryInterface
{
    /** @return Monitor[] */
    public function findPaginated(int $page, int $limit): array;

    public function countTotal(): int;

    public function findAll(): array;

    public function findDueForChecking(): array;

    public function findDueMonitors(): array;

    public function findByIds(array $ids): array;

    public function findByStatus(MonitorStatus $status): array;

    public function save(Monitor $monitor): void;

    public function remove(Monitor $monitor): void;

    public function exists(MonitorId $id): bool;
}
