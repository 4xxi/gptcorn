<?php

declare(strict_types = 1);

namespace App\Component\CollectionImport;

use App\Entity\CollectionImport;

interface CollectionImportManagerInterface
{
    public function import(CollectionImport $collectionImport): void;

    public function updateStatus(CollectionImport $collectionImport, string $status): void;
}
