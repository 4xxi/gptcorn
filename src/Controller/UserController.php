<?php

namespace App\Controller;

use App\Component\File\AvatarFileHandlerInterface;
use App\Component\User\Modifier\UserPasswordModifierInterface;
use App\Entity\User;
use App\Form\UserProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/profile')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordModifierInterface $userPasswordModifier,
        private readonly AvatarFileHandlerInterface $fileHandler,
    ) {
    }

    #[Route('/', name: 'app_user_profile_show', methods: ['GET'])]
    public function show(): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (null === $user) {
            return $this->redirectToRoute('app_default');
        }

        return $this->render(
            'profile/show.html.twig',
            [
                'user' => $this->getUser(),
            ]
        );
    }

    #[Route('/edit', name: 'app_user_profile_show_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (null === $user) {
            return $this->redirectToRoute('app_default');
        }

        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleAvatarUpload($user, $form['avatarFile']->getData());
            $this->userPasswordModifier->modify($user);
            $this->entityManager->flush();

            return $this->redirectToRoute(route: 'app_user_profile_show', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'profile/edit.html.twig',
            [
                'user' => $this->getUser(),
                'form' => $form,
            ]
        );
    }

    #[Route('/show-avatar/{id?}', name: 'app_user_show_avatar', methods: ['GET'])]
    public function showAvatar(AvatarFileHandlerInterface $avatarFileHandler, ?User $user = null): Response
    {
        /** @var User|null $user */
        $user = $user ?? $this->getUser();
        if (null === $user) {
            return $this->redirectToRoute('app_default');
        }

        $stream = $avatarFileHandler->readFileStream((string) $user->getAvatar());
        if (null === $stream) {
            return new BinaryFileResponse($this->getParameter('kernel.project_dir') . '/assets/images/avatar.svg');
        }

        $mimeType = $avatarFileHandler->getMimeType((string) $user->getAvatar()) ?? 'image/jpeg';

        return new StreamedResponse(function () use ($stream) {
            fpassthru($stream);
            fclose($stream);
        }, Response::HTTP_OK, ['Content-Type' => $mimeType]);
    }

    private function handleAvatarUpload(User $user, ?File $avatarFile): void
    {
        if (!$avatarFile) {
            return;
        }

        $this->fileHandler->deleteFile($user->getAvatar());

        $newFilename = $this->fileHandler->saveFile($avatarFile);
        $user->setAvatar($newFilename);
    }
}
