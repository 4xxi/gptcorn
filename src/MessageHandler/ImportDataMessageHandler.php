<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Component\CollectionImport\CollectionImportManagerInterface;
use App\Entity\CollectionImport;
use App\Message\ImportDataMessage;
use App\Repository\CollectionImportRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ImportDataMessageHandler
{
    public function __construct(
        private CollectionImportRepository $collectionImportRepository,
        private LoggerInterface $logger,
        private CollectionImportManagerInterface $collectionImportManager,
    ) {
    }

    public function __invoke(ImportDataMessage $importDataMessage): void
    {
        $collectionImportId = $importDataMessage->getCollectionImportId();
        $collectionImport = $this->collectionImportRepository->find($collectionImportId);
        if (null === $collectionImport) {
            $this->logger->error(sprintf('Could not find collection import with id = %d.', $collectionImportId));

            return;
        }

        try {
            $this->collectionImportManager->import($collectionImport);
        } catch (\Throwable $throwable) {
            $this->logger->error(sprintf('Import %s failed: %s', $collectionImport->getId(),$throwable->getMessage()));
            $this->collectionImportManager->updateStatus($collectionImport, CollectionImport::STATUS_FAILED);
        }
    }
}
