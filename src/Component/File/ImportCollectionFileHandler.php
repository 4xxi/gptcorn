<?php

declare(strict_types = 1);

namespace App\Component\File;

use App\Entity\CollectionImport;
use App\Enum\ImportCollectionFileTypeEnum;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;

final readonly class ImportCollectionFileHandler implements ImportCollectionFileHandlerInterface
{
    public function __construct(private LoggerInterface $logger, private FilesystemOperator $importCollectionStorage,)
    {
    }

    public function getFileType(CollectionImport $collectionImport): ?ImportCollectionFileTypeEnum
    {
        $file = $collectionImport->getFileUpload();
        if ($file) {
            return $this->getFileExtention($file);
        }

        $githubUrl = $collectionImport->getGithubUrl();
        if ($githubUrl) {
            if (preg_match('/\.json$/i', $githubUrl)) {
                return ImportCollectionFileTypeEnum::JSON;
            }

            if (preg_match('/\.csv$/i', $githubUrl)) {
                return ImportCollectionFileTypeEnum::CSV;
            }
        }

        return null;
    }

    public function saveFile(File $file): ?string
    {
        $fileTypeEnum = $this->getFileExtention($file);

        $extension = match ($fileTypeEnum) {
            ImportCollectionFileTypeEnum::JSON => 'json',
            ImportCollectionFileTypeEnum::CSV => 'csv',
        };

        $newFilename = sprintf('%s.%s', uuid_create(), $extension);
        try {
            $stream = fopen($file->getRealPath(), 'rb+');
            $this->importCollectionStorage->writeStream($newFilename, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
            return $newFilename;
        } catch (\Throwable $throwable) {
            $this->logger->error('Could not save new file.', [
                'newFilename' => $newFilename,
                'error' => $throwable,
            ]);

            return null;
        }
    }

    public function deleteFile(string $fileName): void
    {
        try {
            if ($this->importCollectionStorage->fileExists($fileName)) {
                $this->importCollectionStorage->delete($fileName);
            }
        } catch (\Throwable $throwable) {
            $this->logger->error('Could not delete file.', [
                'filename' => $fileName,
                'error' => $throwable,
            ]);
        }
    }

    public function getFileContent(string $fileName): ?string
    {
        try {
            if ($this->importCollectionStorage->fileExists($fileName)) {
                return $this->importCollectionStorage->read($fileName);
            }
        } catch (\Throwable $throwable) {
            $this->logger->error('Could not read file content.', [
                'filePath' => $fileName,
                'error' => $throwable->getMessage(),
            ]);
        }

        return null;
    }

    private function getFileExtention(File $file): ImportCollectionFileTypeEnum
    {
        $extension = strtolower($file->guessExtension());

        if ($extension === 'json') {
            return ImportCollectionFileTypeEnum::JSON;
        }

        if ($extension === 'csv') {
            return ImportCollectionFileTypeEnum::CSV;
        }

        $firstChar = trim($file->getContent())[0] ?? '';

        return in_array($firstChar, ['{', '['])
            ? ImportCollectionFileTypeEnum::JSON
            : ImportCollectionFileTypeEnum::CSV;
    }
}
