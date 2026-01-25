<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Model\Monitor;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class Url
{
    #[ORM\Column(type: Types::STRING)]
    public string $value;

    private function __construct(string $value)
    {
        if (!self::isValid($value)) {
            throw new \InvalidArgumentException('Invalid URL format');
        }

        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function isValid(string $value): bool
    {
        return filter_var($value, \FILTER_VALIDATE_URL) !== false;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
