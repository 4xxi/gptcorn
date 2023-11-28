<?php

namespace App\Controller;

use App\Component\File\ImportCollectionFileHandlerInterface;
use App\Component\SharedEntity\PermissionsResolver\SharedEntityPermissionResolverInterface;
use App\Entity\Collection;
use App\Entity\CollectionImport;
use App\Entity\SharedCollection;
use App\Form\CollectionType;
use App\Form\ImportCollectionType;
use App\Form\ShareCollectionPermissionsType;
use App\Form\ShareType;
use App\Message\ImportDataMessage;
use App\Repository\CollectionImportRepository;
use App\Repository\CollectionRepository;
use App\Repository\SharedCollectionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/collection')]
class CollectionController extends AbstractController
{
    #[Route('/{id}/share', name: 'app_collection_share', methods: ['GET', 'POST'])]
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

            return $this->redirectToRoute('app_collection_share', ['id' => $sharedCollection->getCollection()?->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'collection/share.html.twig',
            [
                'shared_collections' => $sharedCollectionRepository->getWithoutOwnedShare($collection),
                'collection' => $collection,
                'form' => $form,
            ]
        );
    }

    #[Route('/{id}/edit-permissions', name: 'app_collection_edit_share_permissions', methods: ['GET', 'POST'])]
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

            return $this->redirectToRoute('app_collection_share', ['id' => $sharedCollection->getCollection()?->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'collection/share.html.twig',
            [
                'shared_collections' => $sharedCollectionRepository->getWithoutOwnedShare($sharedCollection->getCollection()),
                'collection' => $sharedCollection->getCollection(),
                'form' => $form,
            ]
        );
    }

    #[Route('/{id}/toggleFavorite', name: 'app_collection_favorite', methods: ['GET'])]
    public function toggleFavorite(
        Collection $collection,
        EntityManagerInterface $em,
        Request $request,
    ): Response {
        if (false === $collection->isOwner($this->getUser())) {
            return $this->redirectToRoute('app_default');
        }

        $collection->toggleFavorite();
        $em->persist($collection);
        $em->flush();

        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_collection_index');
    }

    #[Route('/', name: 'app_collection_index', methods: ['GET'])]
    public function index(
        Request $request,
        CollectionRepository $collectionRepository,
        PaginatorInterface $paginator,
    ): Response {
        $query = $collectionRepository->getQueryByUser($this->getUser());
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
        );

        return $this->render(
            'collection/index.html.twig',
            [
                'collections' => $pagination,
            ]
        );
    }

    #[Route('/import', name: 'app_collection_import', methods: ['GET', 'POST'])]
    public function import(
        Request $request,
        MessageBusInterface $messageBus,
        CollectionImportRepository $collectionImportRepository,
        ImportCollectionFileHandlerInterface $importCollectionFileHandler,
        Packages $assetPackage
    ): Response {
        $collectionImport = new CollectionImport();
        $form = $this->createForm(ImportCollectionType::class, $collectionImport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $collectionImport->getFileUpload();
            $fileType = $importCollectionFileHandler->getFileType($collectionImport);
            $collectionImport->setFileType($fileType);
            $collectionImport->setUser($this->getUser());

            if (null !== $file) {
                $filePath = $importCollectionFileHandler->saveFile($file);
                $collectionImport->setFilePath($filePath);
            }

            $collectionImportRepository->save($collectionImport);

            $importDataMessage = new ImportDataMessage($collectionImport->getId());
            $messageBus->dispatch($importDataMessage);

            return $this->redirectToRoute('app_collection_import_show_progress', ['id' => $collectionImport->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'import_collection/index.html.twig',
            [
                'form' => $form,
                'jsonExampleUrl' => $assetPackage->getUrl($this->getParameter('collection_import_json_example_path')),
                'csvExampleUrl' => $assetPackage->getUrl($this->getParameter('collection_import_csv_example_path')),
            ]
        );
    }

    #[Route('/{id}/import-show-progress', name: 'app_collection_import_show_progress', methods: ['GET', 'POST'])]
    public function importShowProgress(CollectionImport $collectionImport): Response {
        if ($collectionImport->getUser() !== $this->getUser()) {
            return $this->redirectToRoute('app_default');
        }

        return $this->render(
            'import_collection/show_progress.html.twig',
            [
                'collectionImport' => $collectionImport,
            ]
        );
    }

    #[Route('/new', name: 'app_collection_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $collection = new Collection();
        $form = $this->createForm(CollectionType::class, $collection);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $collection->setUser($this->getUser());
            $entityManager->persist($collection);

            $sharedCollection = new SharedCollection();
            $sharedCollection->setCollection($collection);
            $sharedCollection->setSharedByUser($this->getUser());
            $sharedCollection->setSharedWithUser($this->getUser());
            $sharedCollection->setWritePermission();
            $entityManager->persist($sharedCollection);

            $entityManager->flush();

            return $this->redirectToRoute('app_collection_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'collection/new.html.twig',
            [
                'collection' => $collection,
                'form' => $form,
            ]
        );
    }

    #[Route('/{id}', name: 'app_collection_show', methods: ['GET'])]
    public function show(
        Collection $collection,
        SharedEntityPermissionResolverInterface $sharedEntityPermissionResolver,
        SharedCollectionRepository $sharedCollectionRepository,
    ): Response {
        if (false === $sharedEntityPermissionResolver->canRead($this->getUser(), $collection)) {
            return $this->redirectToRoute('app_default');
        }

        return $this->render(
            'collection/show.html.twig',
            [
                'is_owner' => $collection->isOwner($this->getUser()),
                'can_write' => $sharedEntityPermissionResolver->canWrite($this->getUser(), $collection),
                'collection' => $collection,
                'shared_collections' => $sharedCollectionRepository->getWithoutOwnedShare($collection),
            ]
        );
    }

    #[Route('/{id}/edit', name: 'app_collection_edit', methods: ['GET', 'POST'])]
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

            return $this->redirectToRoute('app_collection_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'collection/edit.html.twig',
            [
                'collection' => $collection,
                'form' => $form,
            ]
        );
    }

    #[Route('/{id}', name: 'app_collection_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Collection $collection,
        EntityManagerInterface $entityManager,
        SharedEntityPermissionResolverInterface $sharedEntityPermissionResolver
    ): Response {
        if (false === $sharedEntityPermissionResolver->canWrite($this->getUser(), $collection)) {
            return $this->redirectToRoute('app_default');
        }

        if ($this->isCsrfTokenValid('delete' . $collection->getId(), $request->request->get('_token'))) {
            $entityManager->remove($collection);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_collection_index', [], Response::HTTP_SEE_OTHER);
    }
}
