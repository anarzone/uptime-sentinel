<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Repository;

use App\Monitoring\Domain\Model\Alert\AlertRule;
use App\Monitoring\Domain\Model\Monitor\MonitorId;
use Symfony\Component\Uid\UuidV7;

/**
 * @method AlertRule|null find(UuidV7 $uuid)
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
