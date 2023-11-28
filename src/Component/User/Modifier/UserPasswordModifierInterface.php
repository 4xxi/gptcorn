<?php

declare(strict_types=1);

namespace App\Component\User\Modifier;

use App\Entity\User;

interface UserPasswordModifierInterface
{
    public function modify(User $user): void;
}
