<?php

declare(strict_types = 1);

namespace App\Message;

final readonly class ImportDataMessage
{
    public function __construct(private int $collectionImportId)
    {
    }

    public function getCollectionImportId(): int
    {
        return $this->collectionImportId;
    }
}
