<?php

namespace App\Util;

use App\Repository\CategoryRepository;
use App\Repository\CollectionRepository;
use App\Repository\PromptTemplateRepository;
use Symfony\Bundle\SecurityBundle\Security;

readonly class TwigHelper
{

    public function __construct(
        private CategoryRepository $categoryRepository,
        private CollectionRepository $collectionRepository,
        private PromptTemplateRepository $promptTemplateRepository,
        private Security $security,
    ) {
    }

    public function categories()
    {
        return $this->categoryRepository->getByUser($this->security->getUser());
    }

    public function favoriteCollections()
    {
        return $this->collectionRepository->getFavoritesByUser($this->security->getUser());
    }

    public function favoritePromptTemplates()
    {
        return $this->promptTemplateRepository->getFavoritesByUser($this->security->getUser());
    }
}
