<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Repository\PlaceholderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/category')]
class CategoryController extends AbstractController
{
    #[Route('/', name: 'app_category_index', methods: ['GET'])]
    public function index(
        Request $request,
        CategoryRepository $categoryRepository,
        PlaceholderRepository $placeholderRepository,
        PaginatorInterface $paginator,
    ): Response {
        $query = $categoryRepository->getQueryByUser($this->getUser());
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
        );

        return $this->render(
            'category/index.html.twig',
            [
                'categories' => $pagination,
                'placeholders_without_category' => $placeholderRepository->getByUserWithoutCategory($this->getUser()),
            ]
        );
    }

    #[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category->setUser($this->getUser());
            $entityManager->persist($category);
            $entityManager->flush();

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'category/new.html.twig',
            [
                'category' => $category,
                'form' => $form,
            ]
        );
    }

    #[Route('/{id}', name: 'app_category_show', methods: ['GET'])]
    public function show(Category $category, PlaceholderRepository $placeholderRepository): Response
    {
        $placeholders = $placeholderRepository->getByUserAndCategory($this->getUser(), $category);

        return $this->render(
            'category/show.html.twig',
            [
                'category' => $category,
                'placeholders' => $placeholders,
            ]
        );
    }

    #[Route('/{id}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'category/edit.html.twig',
            [
                'category' => $category,
                'form' => $form,
            ]
        );
    }

    #[Route('/{id}', name: 'app_category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
            $entityManager->remove($category);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
    }
}
