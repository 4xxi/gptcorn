<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;

#[AsDoctrineListener(event: ToolEvents::postGenerateSchema)]
final class FixMigrationDefaultSchemaSubscriber
{
    /**
     * @throws Exception
     */
    public function postGenerateSchema(GenerateSchemaEventArgs $generateSchemaEventArgs): void
    {
        $schemaManager = $generateSchemaEventArgs->getEntityManager()
            ->getConnection()
            ->createSchemaManager()
        ;

        foreach ($schemaManager->listSchemaNames() as $namespace) {
            if (!$generateSchemaEventArgs->getSchema()->hasNamespace($namespace)) {
                $generateSchemaEventArgs->getSchema()->createNamespace($namespace);
            }
        }
    }
}
