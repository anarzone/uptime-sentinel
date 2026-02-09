<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Controller;

use App\Security\Domain\Entity\User;

/**
 * Trait for shared controller logic related to User retrieval.
 */
trait ControllerUserIdTrait
{
    /**
     * Extracts the requester ID (string) from the current authenticated user.
     * Returns the UUID string (RFC 4122) for Users or the identifier for other user types.
     */
    protected function getRequesterId(): ?string
    {
        $user = $this->getUser();

        if ($user instanceof User) {
            return $user->getId()->toRfc4122();
        }

        return $user?->getUserIdentifier();
    }
}
