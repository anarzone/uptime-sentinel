<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\ValueObject;

use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

final readonly class OwnerId
{
    private function __construct(public string $value)
    {
        if (!Uuid::isValid($this->value)) {
            throw new \InvalidArgumentException(\sprintf('Invalid owner ID format: %s', $this->value));
        }
    }

    public static function fromString(string $id): self
    {
        return new self($id);
    }

    public static function none(): self
    {
        // Using a null-like or system-specific constant string if needed,
        // but typically we'll use nullable OwnerId in entities or a specific constant.
        // For now, let's treat it as a standard UUID holder.
        return new self((new UuidV7())->toRfc4122()); // Placeholder for "no owner" or specific system ID
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
