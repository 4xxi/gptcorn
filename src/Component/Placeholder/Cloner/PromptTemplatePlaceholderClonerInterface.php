<?php

declare(strict_types = 1);

namespace App\Component\Placeholder\Cloner;

use App\Entity\PromptTemplate;

interface PromptTemplatePlaceholderClonerInterface
{
    public function clone(PromptTemplate $promptTemplate): void;
}
