<?php

declare(strict_types=1);

namespace App\Component\OpenAI\ResponseGetter;


use App\Enum\OpenAIModelEnum;

interface OpenAIResponseGetterInterface
{
    public function getResponse(string $promptContent, OpenAIModelEnum $openAIModelEnum): array;
}
