<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Service;

use App\Monitoring\Domain\Model\Monitor\Monitor;
use App\Monitoring\Domain\ValueObject\OwnerId;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class MonitorAuthorizationService
{
    public function requireOwnership(Monitor $monitor, OwnerId $requesterId, bool $isAdmin): void
    {
        if ($isAdmin) {
            return;
        }

        if ($monitor->ownerId === null || $monitor->ownerId !== $requesterId->value) {
            throw new AccessDeniedException('You do not have permission to access or modify this monitor.');
        }
    }

    public function isOwner(Monitor $monitor, OwnerId $requesterId, bool $isAdmin): bool
    {
        if ($isAdmin) {
            return true;
        }

        return $monitor->ownerId !== null && $monitor->ownerId === $requesterId->value;
    }
}
