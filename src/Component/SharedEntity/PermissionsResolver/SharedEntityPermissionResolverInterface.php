<?php

declare(strict_types = 1);

namespace App\Component\SharedEntity\PermissionsResolver;

use App\Entity\SharedEntityInterface;
use App\Entity\User;

interface SharedEntityPermissionResolverInterface
{
    public function canRead(?User $user, SharedEntityInterface $sharedEntity): bool;

    public function canWrite(?User $user, SharedEntityInterface $sharedEntity): bool;
}
