<?php

declare(strict_types = 1);

namespace App\Entity;

interface SharedEntityInterface
{
    public function isOwner(?User $user): bool;

    public function isSharedWith(?User $user): bool;

    public function getPermissions(User $user): ?int;

    public function getUser(): ?User;
}
