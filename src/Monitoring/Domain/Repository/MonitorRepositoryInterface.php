<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Repository;

use App\Monitoring\Domain\Model\Monitor\Monitor;
use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Model\Monitor\MonitorStatus;
use App\Monitoring\Domain\ValueObject\OwnerId;

/**
 * @method Monitor|null find(MonitorId|string $id)
 */
interface MonitorRepositoryInterface
{
    public function findById(MonitorId|string $id): ?Monitor;

    /** @return Monitor[] */
    public function findPaginated(int $page, int $limit, ?OwnerId $ownerId = null): array;

    public function countTotal(?OwnerId $ownerId = null): int;

    public function findAll(): array;

    /** @return Monitor[] */
    public function findAllByOwner(?OwnerId $ownerId): array;

    public function findDueForChecking(): array;

    public function findDueMonitors(): array;

    public function findByIds(array $ids): array;

    public function findByStatus(MonitorStatus $status): array;

    public function save(Monitor $monitor): void;

    public function remove(Monitor $monitor): void;

    public function exists(MonitorId|string $id): bool;
}
