<?php

namespace App\Entity;

trait SharedPermissionsTrait
{
    public function setReadPermission(): void
    {
        $this->permissions |= EntityWithPermissionsInterface::READ_PERMISSION;
    }

    public function setWritePermission(): void
    {
        $this->permissions |= EntityWithPermissionsInterface::WRITE_PERMISSION;
    }

    public function removeReadPermission(): void
    {
        $this->permissions &= ~EntityWithPermissionsInterface::READ_PERMISSION;
    }

    public function removeWritePermission(): void
    {
        $this->permissions &= ~EntityWithPermissionsInterface::WRITE_PERMISSION;
    }
}
