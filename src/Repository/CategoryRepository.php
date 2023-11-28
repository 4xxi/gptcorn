<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function getQueryBulderByUser(User $user): QueryBuilder
    {
        return $this->createQueryBuilder('category')
            ->andWhere('category.user = :user')
            ->setParameter('user', $user)
            ->addOrderBy('category.title', 'asc')
            ->distinct();
    }

    public function getQueryByUser(User $user): Query
    {
        return $this->createQueryBuilder('category')
            ->andWhere('category.user = :user')
            ->setParameter('user', $user)
            ->addOrderBy('category.title', 'asc')
            ->distinct()
            ->getQuery();
    }

    public function getByUser(User $user)
    {
        return $this->getQueryByUser($user)->getResult();
    }

    public function save(Category $category): void
    {
        $this->getEntityManager()->persist($category);
        $this->getEntityManager()->flush();
    }

    public function delete(Category $category): void
    {
        $this->getEntityManager()->remove($category);
        $this->getEntityManager()->flush();
    }
}
