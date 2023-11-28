<?php

declare(strict_types = 1);

namespace App\Component\SharedEntity\PermissionsResolver;

use App\Entity\EntityWithPermissionsInterface;
use App\Entity\SharedEntityInterface;
use App\Entity\User;

final readonly class SharedEntityPermissionResolver implements SharedEntityPermissionResolverInterface
{

    public function canRead(?User $user, SharedEntityInterface $sharedEntity): bool
    {
        return $sharedEntity->isOwner($user)
            || $this->hasPermission($user, $sharedEntity, EntityWithPermissionsInterface::READ_PERMISSION)
        ;
    }

    public function canWrite(?User $user, SharedEntityInterface $sharedEntity): bool
    {
        return $sharedEntity->isOwner($user)
            || $this->hasPermission($user, $sharedEntity, EntityWithPermissionsInterface::WRITE_PERMISSION)
        ;
    }

    private function hasPermission(?User $user, SharedEntityInterface $sharedEntity, int $permission): bool
    {
        if (!$user) {
            return false;
        }

        $sharedEntityPermissions = $sharedEntity->getPermissions($user);
        if (null === $sharedEntityPermissions) {
            return false;
        }

        return ($sharedEntityPermissions & $permission) === $permission;
    }
}
