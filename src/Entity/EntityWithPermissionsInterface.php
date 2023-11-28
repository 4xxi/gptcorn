<?php

declare(strict_types = 1);

namespace App\Entity;

interface EntityWithPermissionsInterface
{
    public const READ_PERMISSION = 0b000;
    public const WRITE_PERMISSION = 0b001;

    public function getPermissions(): ?int;
}
