<?php

namespace App\Controller;

use App\Repository\CollectionRepository;
use App\Repository\PromptRepository;
use App\Repository\PromptTemplateRepository;
use App\Repository\SharedCollectionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_default')]
    public function index(
        PromptTemplateRepository $promptTemplateRepository,
        PromptRepository $promptRepository,
        CollectionRepository $collectionRepository,
        SharedCollectionRepository $sharedCollectionRepository,
    ): Response {
        $favoritePromptTemplates = $promptTemplateRepository->getFavorites($this->getUser());
        $lastPrompts = $promptRepository->getLatestByUser($this->getUser(), 10);
        $favoriteCollections = $collectionRepository->getFavoritesByUser($this->getUser());
        $sharedCollections = $sharedCollectionRepository->getBySharedWithUser($this->getUser());

        return $this->render(
            'default/index.html.twig',
            [
                'favoritePromptTemplates' => $favoritePromptTemplates,
                'lastPrompts' => $lastPrompts,
                'favoriteCollections' => $favoriteCollections,
                'sharedCollections' => $sharedCollections,
            ]
        );
    }
}
