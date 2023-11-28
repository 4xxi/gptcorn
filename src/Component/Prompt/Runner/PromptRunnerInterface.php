<?php

declare(strict_types = 1);

namespace App\Component\Prompt\Runner;

use App\Entity\Collection;
use App\Entity\Prompt;
use App\Enum\OpenAIModelEnum;

interface PromptRunnerInterface
{
    public function run(Prompt $prompt, OpenAIModelEnum $openAIModelEnum, ?Collection $collection = null): void;

    public function initiatePromptRun(Prompt $prompt, ?Collection $collection = null): void;
}
