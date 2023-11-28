<?php

namespace App\Controller;

use App\Component\SharedEntity\PermissionsResolver\SharedEntityPermissionResolverInterface;
use App\Entity\Category;
use App\Entity\Collection;
use App\Entity\Placeholder;
use App\Form\PlaceholderType;
use App\Repository\PlaceholderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/placeholder')]
class PlaceholderController extends AbstractController
{
    #[Route('/{id}/clone', name: 'app_placeholder_clone', methods: ['GET'])]
    public function clone(
        Placeholder $placeholder,
        EntityManagerInterface $entityManager,
        SharedEntityPermissionResolverInterface $sharedEntityPermissionResolver,
    ): Response {
        if (false === $sharedEntityPermissionResolver->canRead($this->getUser(), $placeholder)) {
            return $this->redirectToRoute('app_default');
        }

        $newPlaceholder = $placeholder->clone();
        $newPlaceholder->setKey("clone_of_".$placeholder->getKey());
        $newPlaceholder->setHeadline("Clone of".$placeholder->getHeadline());
        $newPlaceholder->setUser($this->getUser());

        $isOwner = $placeholder->isOwner($this->getUser());
        if (true === $isOwner) {
            $newPlaceholder->setCategories($placeholder->getCategories());
            $newPlaceholder->setIsFavorite($placeholder->isFavorite());
        }

        $entityManager->persist($newPlaceholder);
        $entityManager->flush();

        return $this->redirectToRoute('app_placeholder_edit', ['id' => $newPlaceholder->getId()]);
    }

    #[Route('/{id}/toggleFavorite', name: 'app_placeholder_favorite', methods: ['GET'])]
    public function toggleFavorite(
        Placeholder $placeholder,
        EntityManagerInterface $em,
        Request $request,
        SharedEntityPermissionResolverInterface $sharedEntityPermissionResolver
    ): Response {
        if (false === $sharedEntityPermissionResolver->canWrite($this->getUser(), $placeholder)) {
            return $this->redirectToRoute('app_default');
        }

        $placeholder->toggleFavorite();
        $em->persist($placeholder);
        $em->flush();

        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_placeholder_index');
    }

    #[Route('/', name: 'app_placeholder_index', methods: ['GET'])]
    public function index(
        Request $request,
        PlaceholderRepository $placeholderRepository,
        PaginatorInterface $paginator,
    ): Response {
        $query = $placeholderRepository->getQueryByUser($this->getUser());
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
        );

        return $this->render(
            'placeholder/index.html.twig',
            [
                'placeholders' => $pagination,
                'category' => null,
            ]
        );
    }

    #[Route('/category/{id}', name: 'app_placeholder_category', methods: ['GET'])]
    public function category(Category $category, PlaceholderRepository $placeholderRepository): Response
    {
        return $this->render(
            'placeholder/index.html.twig',
            [
                'placeholders' => $placeholderRepository->getByUserAndCategory($this->getUser(), $category),
                'category' => $category,
            ]
        );
    }

    #[Route('/new', name: 'app_placeholder_new', methods: ['GET', 'POST'])]
    public function newWithoutCategory(Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->new($request, $entityManager, 'app_placeholder_index');
    }

    #[Route('category/{id}/new', name: 'app_placeholder_new_with_category', methods: ['GET', 'POST'])]
    public function newWithCategory(Category $category, Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->new($request, $entityManager, 'app_category_show', ['id' => $category->getId()], $category);
    }

    private function new(
        Request $request,
        EntityManagerInterface $entityManager,
        string $redirectPath,
        array $redirectParams = [],
        ?Category $category = null
    ): Response {
        $placeholder = new Placeholder();
        if ($category) {
            $placeholder->addCategory($category);
        }

        $form = $this->createForm(PlaceholderType::class, $placeholder, ['is_owner' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $placeholder->setUser($this->getUser());
            $entityManager->persist($placeholder);
            $entityManager->flush();

            return $this->redirectToRoute($redirectPath, $redirectParams, Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'placeholder/new.html.twig',
            [
                'placeholder' => $placeholder,
                'form' => $form,
            ]
        );
    }

    #[Route('/{id}', name: 'app_placeholder_show', methods: ['GET'])]
    public function show(
        Placeholder $placeholder,
        SharedEntityPermissionResolverInterface $sharedEntityPermissionResolver
    ): Response {
        if (false === $sharedEntityPermissionResolver->canRead($this->getUser(), $placeholder)) {
            return $this->redirectToRoute('app_default');
        }

        return $this->render(
            'placeholder/show.html.twig',
            [
                'placeholder' => $placeholder,
            ]
        );
    }

    #[Route('/{id}/edit', name: 'app_placeholder_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Placeholder $placeholder,
        EntityManagerInterface $entityManager,
        SharedEntityPermissionResolverInterface $sharedEntityPermissionResolver
    ): Response {
        if (false === $sharedEntityPermissionResolver->canWrite($this->getUser(), $placeholder)) {
            return $this->redirectToRoute('app_default');
        }

        $form = $this->createForm(PlaceholderType::class, $placeholder, [
            'is_owner' => $placeholder->isOwner($this->getUser()),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_placeholder_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'placeholder/edit.html.twig',
            [
                'placeholder' => $placeholder,
                'form' => $form,
            ]
        );
    }

    #[Route('/{id}/collection/{collectionId}/edit', name: 'app_placeholder_in_collection_edit', methods: ['GET', 'POST'])]
    #[ParamConverter('collection', class: Collection::class, options: ['id' => 'collectionId'])]
    public function editInCollection(
        Request $request,
        Placeholder $placeholder,
        Collection $collection,
        EntityManagerInterface $entityManager,
        SharedEntityPermissionResolverInterface $sharedEntityPermissionResolver
    ): Response {
        if (false === $sharedEntityPermissionResolver->canWrite($this->getUser(), $placeholder)) {
            return $this->redirectToRoute('app_default');
        }

        $isOwner = $placeholder->isOwner($this->getUser());
        $form = $this->createForm(PlaceholderType::class, $placeholder, [
            'is_owner' => $isOwner,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute(
                $isOwner ? 'app_collection_show' : 'app_shared_collection_show',
                ['id' => $collection->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render(
            'placeholder/edit.html.twig',
            [
                'placeholder' => $placeholder,
                'form' => $form,
                'collection' => $collection,
                'isOwner' => $isOwner,
            ]
        );
    }

    #[Route('/{id}', name: 'app_placeholder_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Placeholder $placeholder,
        EntityManagerInterface $entityManager,
        SharedEntityPermissionResolverInterface $sharedEntityPermissionResolver
    ): Response {
        if (false === $sharedEntityPermissionResolver->canWrite($this->getUser(), $placeholder)) {
            return $this->redirectToRoute('app_default');
        }

        if ($this->isCsrfTokenValid('delete'.$placeholder->getId(), $request->request->get('_token'))) {
            $entityManager->remove($placeholder);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_placeholder_index', [], Response::HTTP_SEE_OTHER);
    }
}
