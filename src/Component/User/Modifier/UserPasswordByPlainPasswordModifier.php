<?php

declare(strict_types=1);

namespace App\Component\User\Modifier;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UserPasswordByPlainPasswordModifier implements UserPasswordModifierInterface
{
    public function __construct(
        private UserPasswordHasherInterface $userPasswordHasher,
        private UserRepository $userRepository,
    ) {
    }

    public function modify(User $user): void
    {
        $plainPassword = $user->getPlainPassword();
        if ('' === trim($plainPassword)) {
            return;
        }

        $hashedPassword = $this->userPasswordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);
    }
}
