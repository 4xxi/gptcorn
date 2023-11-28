<?php

namespace App\Component\File;

use Symfony\Component\HttpFoundation\File\File;

interface AvatarFileHandlerInterface
{
    public function downloadAndSaveByUrl(string $url): ?string;

    public function readFileStream(string $filePath);

    public function getMimeType(string $filePath): ?string;

    public function deleteFile(?string $fileName): void;

    public function saveFile(File $file, string $defaultExtension): ?string;
}
