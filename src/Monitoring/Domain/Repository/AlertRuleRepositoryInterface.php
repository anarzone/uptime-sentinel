<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Repository;

use App\Monitoring\Domain\Model\Alert\AlertRule;
use App\Monitoring\Domain\Model\Monitor\MonitorId;

/**
 * @method AlertRule|null find(string $id)
 */
interface AlertRuleRepositoryInterface
{
    /** @return AlertRule[] */
    public function findPaginated(int $page, int $limit, ?\App\Monitoring\Domain\ValueObject\OwnerId $ownerId = null): array;

    public function countTotal(?\App\Monitoring\Domain\ValueObject\OwnerId $ownerId = null): int;

    public function findAll(): array;

    public function findByMonitorId(MonitorId $monitorId): array;

    public function findEnabledByMonitorId(MonitorId $monitorId): array;

    public function save(AlertRule $alertRule): void;

    public function remove(AlertRule $alertRule): void;

    public function exists(string $id): bool;

    public function findById(string $id): ?AlertRule;
}
