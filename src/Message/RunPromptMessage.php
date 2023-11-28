<?php

declare(strict_types=1);

namespace App\Message;

final readonly class RunPromptMessage
{
    public function __construct(private int $promptId, private ?int $collectionId = null)
    {
    }

    public function getPromptId(): int
    {
        return $this->promptId;
    }

    public function getCollectionId(): ?int
    {
        return $this->collectionId;
    }
}
