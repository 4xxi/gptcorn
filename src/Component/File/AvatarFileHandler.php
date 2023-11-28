<?php

declare(strict_types = 1);

namespace App\Component\File;

use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class AvatarFileHandler implements AvatarFileHandlerInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private FilesystemOperator $avatarStorage,
        private LoggerInterface $logger,
    ) {
    }

    public function saveFile(File $file, string $defaultExtension = 'jpg'): ?string
    {
        $newFilename = sprintf('%s.%s', uuid_create(), $file->guessExtension() ?? $defaultExtension);
        try {
            $stream = fopen($file->getRealPath(), 'rb+');
            $this->avatarStorage->writeStream($newFilename, $stream);
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

    public function readFileStream(string $filePath)
    {
        try {
            return $this->avatarStorage->readStream($filePath);
        } catch (\Throwable $throwable) {
            $this->logger->error('Could not read file.', [
                'filePath' => $filePath,
                'error' => $throwable,
            ]);

            return null;
        }
    }

    public function getMimeType(string $filePath): ?string
    {
        try {
            return $this->avatarStorage->mimeType($filePath);
        } catch (\Throwable $throwable) {
            $this->logger->error('Could not get file mimeType.', [
                'filePath' => $filePath,
                'error' => $throwable,
            ]);

            return null;
        }
    }

    public function downloadAndSaveByUrl(string $url): ?string
    {
        $targetPath = null;

        try {
            $response = $this->httpClient->request('GET', $url);
            $content = $response->getContent();
            $contentType = $response->getInfo()['content_type'] ?? 'image/jpeg';

            $fileName = $this->generateFileName($contentType);
            $this->avatarStorage->write($fileName, $content);

            return $fileName;
        } catch (\Throwable $throwable) {
            $this->logger->error('Could not download and save file.', [
                'url' => $url,
                'targetPath' => $targetPath,
                'error' => $throwable,
            ]);

            return null;
        }
    }

    public function deleteFile(?string $fileName): void
    {
        if (null === $fileName) {
            return;
        }

        try {
            if ($this->avatarStorage->fileExists($fileName)) {
                $this->avatarStorage->delete($fileName);
            }
        } catch (\Throwable $throwable) {
            $this->logger->error('Could not delete file.', [
                'filename' => $fileName,
                'error' => $throwable,
            ]);
        }
    }

    private function generateFileName(string $contentType): string
    {
        $extension = MimeTypes::getDefault()->getExtensions($contentType)[0] ?? 'jpg';

        return sprintf('%s.%s', uuid_create(), $extension);
    }
}
