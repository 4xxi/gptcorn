<?php

declare(strict_types = 1);

namespace App\Entity;

abstract class AbstractSharedEntity implements SharedEntityInterface
{
    abstract public function getUser(): ?User;

    abstract public function isSharedWith(?User $user): bool;

    abstract public function getPermissions(User $user): ?int;

    public function isOwner(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if (!$this->getUser()) {
            return true;
        }

        return $user->getId() === $this->getUser()?->getId();
    }
}
