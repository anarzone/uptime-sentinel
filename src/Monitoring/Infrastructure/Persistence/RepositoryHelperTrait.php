<?php

declare(strict_types=1);

namespace App\Monitoring\Infrastructure\Persistence;

/**
 * Trait providing common repository helper methods.
 *
 * This trait eliminates code duplication across repositories by providing
 * commonly used functionality for entity existence checks.
 *
 * Note: This trait provides exists(string $id) for repositories like
 * AlertRuleRepository and EscalationPolicyRepository. MonitorRepository
 * has its own implementation with exists(MonitorId $id) due to interface
 * requirements.
 */
trait RepositoryHelperTrait
{
    /**
     * Check if an entity with the given ID exists.
     *
     * @param string $id The entity ID to check
     *
     * @return bool True if the entity exists, false otherwise
     */
    public function exists(string $id): bool
    {
        return $this->find($id) !== null;
    }
}
