<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Repository;

use App\Monitoring\Domain\Model\Monitor\MonitorHealth;
use App\Monitoring\Domain\Model\Monitor\MonitorId;

/**
 * Repository interface for managing monitor state persistence.
 *
 * Provides methods to save and retrieve monitor health status,
 * which is needed for tracking state changes between monitor checks.
 */
interface MonitorStateRepositoryInterface
{
    /**
     * Get the last known health status for a monitor.
     *
     * @param MonitorId $monitorId The monitor ID
     *
     * @return MonitorHealth|null The last health status, or null if no previous status exists
     */
    public function getLastHealthStatus(MonitorId $monitorId): ?MonitorHealth;

    /**
     * Save the current health status for a monitor.
     *
     * @param MonitorId     $monitorId The monitor ID
     * @param MonitorHealth $health    The current health status to save
     */
    public function saveHealthStatus(MonitorId $monitorId, MonitorHealth $health): void;
}
