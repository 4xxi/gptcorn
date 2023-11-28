<?php

namespace App\Repository;

use App\Entity\Collection;
use App\Entity\Placeholder;
use App\Entity\PromptTemplate;
use App\Entity\CollectionImport;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CollectionImport>
 *
 * @method CollectionImport|null find($id, $lockMode = null, $lockVersion = null)
 * @method CollectionImport|null findOneBy(array $criteria, array $orderBy = null)
 * @method CollectionImport[]    findAll()
 * @method CollectionImport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CollectionImportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CollectionImport::class);
    }

    public function save(CollectionImport $collectionImport): void
    {
        $this->getEntityManager()->persist($collectionImport);
        $this->getEntityManager()->flush();
    }
}
