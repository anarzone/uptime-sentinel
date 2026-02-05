<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Model\Monitor;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

#[ORM\Embeddable]
final readonly class MonitorId
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'string', length: 36)]
    public string $value;

    public function __construct(string $value)
    {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException('Invalid UUID format');
        }

        $this->value = $value;
    }

    public static function generate(): self
    {
        return new self(new UuidV7()->toRfc4122());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
