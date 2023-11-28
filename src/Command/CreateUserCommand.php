<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @psalm-suppress UnnecessaryVarAnnotation
 */
#[AsCommand(name: 'app:user:create', description: 'Create an user')]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED)
            ->addOption('password', null, InputOption::VALUE_REQUIRED)
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Add a ROLE_ADMIN role to user')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyleOutput = new SymfonyStyle($input, $output);

        /** @var string|null $email */
        $email = $input->getOption('email');
        if (null === $email) {
            $symfonyStyleOutput->error('The "--email" option is required.');

            return self::INVALID;
        }

        /** @var string|null $password */
        $password = $input->getOption('password');
        if (null === $password) {
            $symfonyStyleOutput->error('The "--password" option is required.');

            return self::INVALID;
        }

        if (null !== $this->userRepository->findOneBy(['email' => $email])) {
            $symfonyStyleOutput->error(sprintf('User with email %s already exists.', $email));

            return self::INVALID;
        }

        $user = new User();
        $user->setEmail($email);

        $hashedPassword = $this->userPasswordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        /** @var bool|null $admin */
        $admin = $input->getOption('admin');
        if (true === $admin) {
            $user->addRole(User::ROLE_ADMIN);
        }

        $this->userRepository->save($user);

        return self::SUCCESS;
    }
}
