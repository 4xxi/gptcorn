<?php

declare(strict_types=1);

namespace App\Enum;

enum OpenAIModelEnum: string
{
    case GPT4 = 'gpt-4';
    case GPT4_TURBO = 'gpt-4-1106-preview';
}
