<?php

declare(strict_types = 1);

namespace App\Component\CollectionImport;

use App\Component\File\ImportCollectionFileHandlerInterface;
use App\Entity\Category;
use App\Entity\Collection;
use App\Entity\CollectionImport;
use App\Entity\Placeholder;
use App\Entity\PromptTemplate;
use App\Entity\User;
use App\Enum\ImportCollectionFileTypeEnum;
use App\Repository\CollectionImportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

final readonly class CollectionImportManager implements CollectionImportManagerInterface
{
    public function __construct(
        private CollectionImportRepository $collectionImportRepository,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private ImportCollectionFileHandlerInterface $importCollectionFileHandler,
        private HubInterface $hub,
    ) {
    }

    public function import(CollectionImport $collectionImport): void
    {
        $this->updateStatus($collectionImport, CollectionImport::STATUS_IN_PROGRESS);

        $user = $collectionImport->getUser();
        if (null === $user) {
            $this->logger->error(
                sprintf('Collection import with id = %d does not have a linked user.', $collectionImport->getId())
            );
            $this->updateStatus($collectionImport, CollectionImport::STATUS_FAILED);

            return;
        }

        $fileContent = $this->getFileContent($collectionImport);
        if ('' === $fileContent) {
            $this->logger->error('No content found in file or github url provided for import.');
            $this->updateStatus($collectionImport, CollectionImport::STATUS_FAILED);

            return;
        }

        $fileType = $collectionImport->getFileType();

        if ($fileType === ImportCollectionFileTypeEnum::JSON) {
            $this->processJSONImport($user, $fileContent);
        }

        if ($fileType === ImportCollectionFileTypeEnum::CSV) {
            $this->processCSVImport($user, $fileContent);
        }

        $this->entityManager->flush();

        if (null !== $collectionImport->getFilePath()) {
            $this->importCollectionFileHandler->deleteFile($collectionImport->getFilePath());
        }

        $this->updateStatus($collectionImport, CollectionImport::STATUS_COMPLETED);
    }

    public function updateStatus(CollectionImport $collectionImport, string $status): void
    {
        $collectionImport->setStatus($status);
        $this->collectionImportRepository->save($collectionImport);

        $this->sendUpdate($collectionImport);
    }

    private function sendUpdate(CollectionImport $collectionImport): void
    {
        try {
            $update = new Update(
                sprintf('/import-collection-updated-%d', $collectionImport->getId()),
                json_encode(['id' => $collectionImport->getId(), 'status' => $collectionImport->getStatus()],
                    JSON_THROW_ON_ERROR)
            );
            $this->hub->publish($update);
        } catch (Throwable $throwable) {
            $this->logger->error(
                sprintf(
                    'Failed to send update via mercure for the collection import with id = %d.',
                    $collectionImport->getId()
                ),
                [
                    'data' => ['collectionImportId' => $collectionImport->getId()],
                    'error' => $throwable->getMessage(),
                ]
            );
        }
    }

    private function findOrCreateCollection(
        int $importUuid,
        string $title,
        string $description,
        User $user,
        array &$collectionMap
    ): Collection {
        if (isset($collectionMap[$title])) {
            return $collectionMap[$title];
        }

        $collection = $this->createCollection($importUuid, $title, $description, $user);
        $collectionMap[$title] = $collection;

        return $collection;
    }

    private function createCollection(
        int $importUuid,
        string $title,
        string $description,
        User $user
    ): Collection {
        $collection = new Collection();
        $collection->setTitle(sprintf('Import_%s_%s', $importUuid, $title));
        $collection->setDescription($description);
        $collection->setUser($user);

        return $collection;
    }

    private function findOrCreatePromptTemplate(
        int $importUuid,
        string $title,
        string $content,
        User $user,
        array &$promptTemplateMap
    ): PromptTemplate {
        if (isset($promptTemplateMap[$title])) {
            return $promptTemplateMap[$title];
        }

        $promptTemplate = $this->createPromptTemplate($importUuid, $title, $content, $user);
        $promptTemplateMap[$title] = $promptTemplate;

        return $promptTemplate;
    }

    private function createPromptTemplate(
        int $importUuid,
        string $title,
        string $content,
        User $user
    ): PromptTemplate {
        $promptTemplate = new PromptTemplate();
        $promptTemplate->setTitle(sprintf('Import_%s_%s', $importUuid, $title));
        $promptTemplate->setContent($content);
        $promptTemplate->setUser($user);

        return $promptTemplate;
    }

    private function createPlaceholder(
        int $importUuid,
        string $key,
        string $value,
        string $headline,
        ?string $description,
        User $user
    ): Placeholder {
        $placeholder = new Placeholder();
        $placeholder->setKey($key);
        $placeholder->setValue($value);
        $placeholder->setHeadline(sprintf('Import_%s_%s', $importUuid, $headline));
        $placeholder->setDescription($description);
        $placeholder->setUser($user);

        return $placeholder;
    }

    private function createCategory(int $importUuid, User $user): Category
    {
        $category = new Category();
        $category->setTitle(sprintf('Import_%s', $importUuid));
        $category->setUser($user);

        return $category;
    }

    private function convertToRawGithubUrl(string $githubUrl): string {
        $parsedUrl = parse_url($githubUrl);
        $path = $parsedUrl['path'] ?? '';

        $path = str_replace('/blob', '', $path);

        return sprintf('https://raw.githubusercontent.com%s', $path);
    }

    private function processCSVImport(User $user, string $fileContent): void
    {
        $importUuid = time();

        $collectionMap = [];
        $promptTemplateMap = [];
        $category = null;
        $csvLines = explode(PHP_EOL, $fileContent);
        foreach ($csvLines as $lineNumer => $line) {
            if (
                0 === $lineNumer
                || '' === (trim($line))
            ) {
                continue;
            }

            $data = str_getcsv($line);
            $collectionTitle = $data[0] ?? '';
            $collectionDescription = $data[1] ?? '';
            $templateTitle = $data[2] ?? '';
            $templateContent = $data[3] ?? '';
            $placeholderKey = $data[4] ?? '';
            $placeholderValue = $data[5] ?? '';
            $placeholderHeadline = $data[6] ?? '';
            $placeholderDescription = $data[7] ?? null;

            $collection = $this->findOrCreateCollection(
                $importUuid,
                $collectionTitle,
                $collectionDescription,
                $user,
                $collectionMap
            );
            if ($templateTitle && $templateContent) {
                $promptTemplate = $this->findOrCreatePromptTemplate(
                    $importUuid,
                    $templateTitle,
                    $templateContent,
                    $user,
                    $promptTemplateMap
                );
                $collection->addPromptTemplate($promptTemplate);

                $this->entityManager->persist($promptTemplate);
            }

            if ($placeholderKey && $placeholderValue && $placeholderHeadline) {
                if (null === $category) {
                    $category = $this->createCategory($importUuid, $user);
                    $this->entityManager->persist($category);
                }

                $placeholder = $this->createPlaceholder(
                    $importUuid,
                    $placeholderKey,
                    $placeholderValue,
                    $placeholderHeadline,
                    $placeholderDescription ?: null,
                    $user
                );
                $collection->addPlaceholder($placeholder);
                $category->addPlaceholder($placeholder);

                $this->entityManager->persist($placeholder);
            }

            $this->entityManager->persist($collection);
        }
    }

    private function processJSONImport(User $user, string $fileContent): void
    {
        $importUuid = time();

        try {
            $collectionsData = json_decode($fileContent, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $throwable) {
            $this->logger->error('Failed to decode JSON: ' . $throwable->getMessage());
            $this->updateStatus($collectionImport, CollectionImport::STATUS_FAILED);

            return;
        }

        $category = null;
        foreach ($collectionsData['collections'] ?? [] as $collectionData) {
            $collection = $this->createCollection(
                $importUuid,
                $collectionData['title'] ?? '',
                $collectionData['description'] ?? '',
                $user
            );

            foreach ($collectionData['promptTemplates'] ?? [] as $templateData) {
                $promptTemplate = $this->createPromptTemplate(
                    $importUuid,
                    $templateData['title'],
                    $templateData['content'] ?? '',
                    $user
                );

                $collection->addPromptTemplate($promptTemplate);

                $this->entityManager->persist($promptTemplate);
            }

            foreach ($collectionData['placeholders'] ?? [] as $placeholderData) {
                if (null === $category) {
                    $category = $this->createCategory($importUuid, $user);
                    $this->entityManager->persist($category);
                }

                $placeholder = $this->createPlaceholder(
                    $importUuid,
                    $placeholderData['key'] ?? '',
                        $placeholderData['value'] ?? '',
                        $placeholderData['headline']  ?? '',
                        $placeholderData['description'] ?? null,
                    $user
                );
                $collection->addPlaceholder($placeholder);
                $category->addPlaceholder($placeholder);

                $this->entityManager->persist($placeholder);
            }

            $this->entityManager->persist($collection);
        }
    }

    private function getFileContent(CollectionImport $collectionImport): string
    {
        $githubUrl = $collectionImport->getGithubUrl();
        $filePath = $collectionImport->getFilePath();

        if (null === $githubUrl && null === $filePath) {
            return '';
        }

        if (null !== $githubUrl) {
            if (!str_contains($githubUrl, 'raw.githubusercontent.com')) {
                $githubUrl = $this->convertToRawGithubUrl($githubUrl);
            }

            try {
                return $this->httpClient->request('GET', $githubUrl)->getContent();
            } catch (Throwable $throwable) {
                $this->logger->error('Failed to download file from GitHub: ' . $throwable->getMessage());
                $this->updateStatus($collectionImport, CollectionImport::STATUS_FAILED);

                return '';
            }
        }

        if (null !== $filePath) {
            return (string) $this->importCollectionFileHandler->getFileContent($filePath);
        }

        return '';
    }
}
