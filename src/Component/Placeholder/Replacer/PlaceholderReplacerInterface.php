<?php

declare(strict_types = 1);

namespace App\Component\Placeholder\Replacer;

use App\Entity\Collection;
use App\Entity\Prompt;

interface PlaceholderReplacerInterface
{
    public function replace(Prompt $prompt, ?Collection $collection = null): string;
}
