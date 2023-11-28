<?php

declare(strict_types = 1);

namespace App\Component\File;

use App\Entity\CollectionImport;
use App\Enum\ImportCollectionFileTypeEnum;
use Symfony\Component\HttpFoundation\File\File;

interface ImportCollectionFileHandlerInterface
{
    public function getFileType(CollectionImport $collectionImport): ?ImportCollectionFileTypeEnum;

    public function saveFile(File $file): ?string;

    public function getFileContent(string $fileName): ?string;

    public function deleteFile(string $fileName): void;
}
