<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Model\Monitor;

/**
 * Value object representing the state of a monitor at a point in time.
 *
 * Encapsulates the health status and timestamp for storage and retrieval,
 * keeping serialization logic in the domain layer rather than infrastructure.
 */
final readonly class MonitorState
{
    public function __construct(
        public MonitorHealth $health,
        public int $timestamp,
    ) {
    }

    /**
     * Serialize the state to JSON for storage.
     */
    public function serialize(): string
    {
        return json_encode([
            'health' => $this->health->value,
            'timestamp' => $this->timestamp,
        ]);
    }

    /**
     * Deserialize state from JSON storage.
     *
     * Returns null if the data is malformed or missing required fields.
     */
    public static function deserialize(string $data): ?self
    {
        $decoded = json_decode($data, true);
        if (!isset($decoded['health'], $decoded['timestamp'])) {
            return null;
        }

        $health = MonitorHealth::tryFrom($decoded['health']);
        if ($health === null) {
            return null;
        }

        return new self(
            $health,
            $decoded['timestamp'],
        );
    }
}
