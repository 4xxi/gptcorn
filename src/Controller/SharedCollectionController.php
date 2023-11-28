<?php

namespace App\Controller;

use App\Component\SharedEntity\PermissionsResolver\SharedEntityPermissionResolverInterface;
use App\Entity\Collection;
use App\Entity\SharedCollection;
use App\Form\CollectionType;
use App\Form\ShareCollectionPermissionsType;
use App\Form\ShareType;
use App\Repository\SharedCollectionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/shared-collection')]
class SharedCollectionController extends AbstractController
{
    #[Route('/', name: 'app_shared_collection_index', methods: ['GET'])]
    public function index(
        Request $request,
        SharedCollectionRepository $sharedCollectionRepository,
        PaginatorInterface $paginator,
    ): Response {
        $query = $sharedCollectionRepository->getQueryBySharedWithUser($this->getUser());
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
        );

        return $this->render(
            'shared_collection/index.html.twig',
            [
                'shared_collections' => $pagination,
            ]
        );
    }

    #[Route('/{id}', name: 'app_shared_collection_show', methods: ['GET'])]
    public function show(
        Collection $collection,
        SharedEntityPermissionResolverInterface $sharedEntityPermissionResolver,
        SharedCollectionRepository $sharedCollectionRepository,
    ): Response {
        if (false === $sharedEntityPermissionResolver->canRead($this->getUser(), $collection)) {
            return $this->redirectToRoute('app_default');
        }

        return $this->render(
            'shared_collection/show.html.twig',
            [
                'is_owner' => $collection->isOwner($this->getUser()),
                'can_write' => $sharedEntityPermissionResolver->canWrite($this->getUser(), $collection),
                'collection' => $collection,
                'shared_collections' => $sharedCollectionRepository->getWithoutOwnedShare($collection),
            ]
        );
    }

    #[Route('/{id}/edit', name: 'app_shared_collection_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Collection $collection,
        EntityManagerInterface $entityManager,
        SharedEntityPermissionResolverInterface $sharedEntityPermissionResolver
    ): Response {
        if (false === $sharedEntityPermissionResolver->canWrite($this->getUser(), $collection)) {
            return $this->redirectToRoute('app_default');
        }

        $form = $this->createForm(CollectionType::class, $collection);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_shared_collection_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'shared_collection/edit.html.twig',
            [
                'collection' => $collection,
                'form' => $form,
            ]
        );
    }

    #[Route('/{id}/share', name: 'app_shared_collection_share', methods: ['GET', 'POST'])]
    public function addNewShare(
        Collection $collection,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        Request $request,
        SharedEntityPermissionResolverInterface $sharedEntityPermissionResolver,
        SharedCollectionRepository $sharedCollectionRepository,
    ): Response {
        if (false === $sharedEntityPermissionResolver->canRead($this->getUser(), $collection)) {
            return $this->redirectToRoute('app_default');
        }

        $form = $this->createForm(ShareType::class, $collection);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $emailToShareWith = $form->get('email')->getData();
            $permission = $form->get('permission')->getData();
            $userToShareWith = $userRepository->findOneBy(['email' => $emailToShareWith]);

            $sharedCollection = new SharedCollection();
            $sharedCollection->setCollection($collection);
            $sharedCollection->setSharedWithUser($userToShareWith);
            $sharedCollection->setSharedByUser($this->getUser());
            $sharedCollection->setPermissions($permission);
            $entityManager->persist($sharedCollection);
            $entityManager->flush();

            return $this->redirectToRoute('app_shared_collection_share', ['id' => $sharedCollection->getCollection()?->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'shared_collection/share.html.twig',
            [
                'shared_collections' => $sharedCollectionRepository->getWithoutOwnedShare($collection),
                'collection' => $collection,
                'form' => $form,
            ]
        );
    }

    #[Route('/{id}/edit-permissions', name: 'app_shared_collection_edit_permissions', methods: ['GET', 'POST'])]
    public function editPermissions(
        Request $request,
        SharedCollection $sharedCollection,
        EntityManagerInterface $entityManager,
        SharedCollectionRepository $sharedCollectionRepository,
        SharedEntityPermissionResolverInterface $sharedEntityPermissionResolver,
    ): Response {
        if (false === $sharedEntityPermissionResolver->canRead($this->getUser(), $sharedCollection->getCollection())) {
            return $this->redirectToRoute('app_default');
        }

        $form = $this->createForm(ShareCollectionPermissionsType::class, $sharedCollection, [
            'email_default' => $sharedCollection->getSharedWithUser()?->getEmail(),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_shared_collection_share', ['id' => $sharedCollection->getCollection()?->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'shared_collection/share.html.twig',
            [
                'shared_collections' => $sharedCollectionRepository->getWithoutOwnedShare($sharedCollection->getCollection()),
                'collection' => $sharedCollection->getCollection(),
                'form' => $form,
            ]
        );
    }

    #[Route('/{id}', name: 'app_shared_collection_delete', methods: ['POST'])]
    public function delete(
        SharedCollection $sharedCollection,
        Request $request,
        EntityManagerInterface $entityManager,
        SharedEntityPermissionResolverInterface $sharedEntityPermissionResolver,
    ): Response {
        if (
            $sharedCollection->getSharedWithUser() !== $this->getUser()
            && false === $sharedEntityPermissionResolver->canWrite($this->getUser(), $sharedCollection->getCollection())
        ) {
            return $this->redirectToRoute('app_default');
        }

        if ($this->isCsrfTokenValid('delete' . $sharedCollection->getId(), $request->request->get('_token'))) {
            $entityManager->remove($sharedCollection);
            $entityManager->flush();

            $referer = $request->headers->get('referer');
            if ($referer) {
                return $this->redirect($referer);
            }
        }

        return $this->redirectToRoute('app_collection_share', ['id' => $sharedCollection->getCollection()?->getId()], Response::HTTP_SEE_OTHER);
    }
}
