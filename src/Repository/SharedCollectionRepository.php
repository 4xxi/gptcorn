<?php

namespace App\Repository;

use App\Entity\Collection;
use App\Entity\Placeholder;
use App\Entity\PromptTemplate;
use App\Entity\SharedCollection;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SharedCollection>
 *
 * @method SharedCollection|null find($id, $lockMode = null, $lockVersion = null)
 * @method SharedCollection|null findOneBy(array $criteria, array $orderBy = null)
 * @method SharedCollection[]    findAll()
 * @method SharedCollection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SharedCollectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SharedCollection::class);
    }

    public function getQueryBySharedWithUser(User $user): Query
    {
        return $this
            ->createQueryBuilder('shared_collection')
            ->innerJoin('shared_collection.collection', 'collection')
            ->andWhere('shared_collection.sharedWithUser = :user')
            ->andWhere('collection.user != :user')
            ->setParameter('user', $user)
            ->addOrderBy('shared_collection.updatedAt', 'desc')
            ->getQuery()
            ;
    }

    /**
     * @return SharedCollection[]
     */
    public function getBySharedWithUser(User $user): array
    {
        return $this->getQueryBySharedWithUser($user)->getResult();
    }

    public function getWithoutOwnedShare(Collection $collection): array
    {
        $query = $this
            ->createQueryBuilder('shared_collection')
            ->andWhere('shared_collection.collection = :collection')
            ->andWhere('shared_collection.sharedWithUser != shared_collection.sharedByUser')
            ->setParameter('collection', $collection)
            ->addOrderBy('shared_collection.updatedAt', 'desc')
            ->getQuery()
        ;

        return $query->getResult();
    }
}
