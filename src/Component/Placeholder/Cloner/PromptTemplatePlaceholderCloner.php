<?php

declare(strict_types = 1);

namespace App\Component\Placeholder\Cloner;

use App\Entity\PromptTemplate;
use App\Entity\User;
use App\Repository\PlaceholderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class PromptTemplatePlaceholderCloner implements PromptTemplatePlaceholderClonerInterface
{
    public function __construct(
        private Security $security,
        private PlaceholderRepository $placeholderRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function clone(PromptTemplate $promptTemplate): void
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        if (null === $user) {
            return;
        }

        $content = $promptTemplate->getContent();

        if (!preg_match_all('/\{([^}]+)}/', $content, $matches)) {
            return;
        }

        foreach ($matches[1] ?? [] as $placeholderKey) {
            $userPlaceholder = $this->placeholderRepository->findOneBy(['user' => $user, 'key' => $placeholderKey]);
            if (null !== $userPlaceholder) {
                continue;
            }

            $placeholder = $this->placeholderRepository->findOneBy(['key' => $placeholderKey]);
            if (null === $placeholder) {
                $this->logger->error(sprintf('Could not find placeholder to clone by key: %s', $placeholderKey));

                continue;
            }

            $clonedPlaceholder = $placeholder->clone();
            $clonedPlaceholder->setUser($user);

            $this->entityManager->persist($clonedPlaceholder);
        }

        $this->entityManager->flush();
    }
}
