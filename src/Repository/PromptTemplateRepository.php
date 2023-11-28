<?php

namespace App\Repository;

use App\Entity\Collection;
use App\Entity\PromptTemplate;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PromptTemplate>
 *
 * @method PromptTemplate|null find($id, $lockMode = null, $lockVersion = null)
 * @method PromptTemplate|null findOneBy(array $criteria, array $orderBy = null)
 * @method PromptTemplate[]    findAll()
 * @method PromptTemplate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PromptTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PromptTemplate::class);
    }

    public function filterByUserAndSortByTitle(User $user, Collection $collection): QueryBuilder
    {
        return $this->createQueryBuilder('pt')
            ->leftJoin('pt.collections', 'c')
            ->leftJoin('c.sharedCollections', 'sc')
            ->where('pt.user = :user')
            ->orWhere('sc.sharedWithUser = :user and sc.collection = :collection')
            ->setParameter('user', $user)
            ->setParameter('collection', $collection)
            ->addOrderBy('pt.title', 'asc')
            ->distinct();
    }

    public function getQueryBuilderByUser(User $user): QueryBuilder
    {
        return $this->createQueryBuilder('pt')
            ->where('pt.user = :user')
            ->setParameter('user', $user)
            ->addOrderBy('pt.title', 'asc')
            ->distinct();
    }

    public function getFavoritesByUser(User $user)
    {
        $query = $this
            ->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.isFavorite = :favorite')
            ->setParameter('user', $user)
            ->setParameter('favorite', true)
            ->addOrderBy('p.title', 'asc')
            ->getQuery()
        ;

        return $query->getResult();
    }

    public function getQueryByUser(User $user): Query
    {
        return $this
            ->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->addOrderBy('p.isFavorite', 'desc')
            ->addOrderBy('p.updatedAt', 'desc')
            ->getQuery()
        ;
    }

    public function getFavorites(User $user): array
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

    public function save(PromptTemplate $promptTemplate): void
    {
        $this->getEntityManager()->persist($promptTemplate);
        $this->getEntityManager()->flush();
    }

    public function delete(PromptTemplate $promptTemplate): void
    {
        $this->getEntityManager()->remove($promptTemplate);
        $this->getEntityManager()->flush();
    }
}
