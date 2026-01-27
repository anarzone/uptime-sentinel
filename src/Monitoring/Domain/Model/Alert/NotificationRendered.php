<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Model\Alert;

/**
 * Represents a rendered notification with optional subject and body.
 */
final readonly class NotificationRendered
{
    public function __construct(
        public ?string $subject,
        public string $body,
    ) {
    }
}
