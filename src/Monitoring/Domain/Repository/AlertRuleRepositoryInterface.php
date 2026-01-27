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
    public function findAll(): array;

    public function findByMonitorId(MonitorId $monitorId): array;

    public function findEnabledByMonitorId(MonitorId $monitorId): array;

    public function save(AlertRule $alertRule): void;

    public function remove(AlertRule $alertRule): void;

    public function exists(string $id): bool;
}
