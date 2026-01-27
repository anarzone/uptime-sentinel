<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Repository;

use App\Monitoring\Domain\Model\Alert\EscalationPolicy;
use App\Monitoring\Domain\Model\Monitor\MonitorId;

/**
 * Repository for managing escalation policies.
 *
 * @method EscalationPolicy|null find(string $id)
 */
interface EscalationPolicyRepositoryInterface
{
    /**
     * Find all applicable policies for a monitor (monitor-specific + global).
     *
     * Returns policies ordered by level (1, 2, 3, ...).
     *
     * @return array<EscalationPolicy>
     */
    public function findApplicableForMonitor(MonitorId $monitorId): array;

    /**
     * Find all policies (optionally filtered by monitor).
     *
     * @return array<EscalationPolicy>
     */
    public function findAll(?MonitorId $monitorId = null): array;

    /**
     * Save a policy (create or update).
     */
    public function save(EscalationPolicy $policy): void;

    /**
     * Delete a policy.
     */
    public function remove(EscalationPolicy $policy): void;

    /**
     * Check if a policy exists.
     */
    public function exists(string $id): bool;
}
