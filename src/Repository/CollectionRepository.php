<?php

namespace App\Repository;

use App\Entity\Collection;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Collection>
 *
 * @method Collection|null find($id, $lockMode = null, $lockVersion = null)
 * @method Collection|null findOneBy(array $criteria, array $orderBy = null)
 * @method Collection[]    findAll()
 * @method Collection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CollectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Collection::class);
    }

    public function getFavoritesByUser(User $user)
    {
        $query = $this
            ->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->andWhere('c.isFavorite = :favorite')
            ->setParameter('user', $user)
            ->setParameter('favorite', true)
            ->addOrderBy('c.title', 'asc')
            ->getQuery()
        ;

        return $query->getResult();
    }

    public function getQueryByUser(User $user): Query
    {
        return $this
            ->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->addOrderBy('c.isFavorite', 'desc')
            ->addOrderBy('c.updatedAt', 'desc')
            ->getQuery()
        ;
    }

    /**
     * @return Collection[]
     */
    public function getPublic(): array
    {
        return $this
            ->createQueryBuilder('c')
            ->andWhere('c.isPublic = :isPublic')
            ->setParameter('isPublic', true)
            ->getQuery()
            ->getResult()
        ;
    }
}
