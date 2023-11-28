<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Collection;
use App\Entity\Placeholder;
use App\Entity\Prompt;
use App\Entity\PromptTemplate;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Placeholder>
 *
 * @method Placeholder|null find($id, $lockMode = null, $lockVersion = null)
 * @method Placeholder|null findOneBy(array $criteria, array $orderBy = null)
 * @method Placeholder[]    findAll()
 * @method Placeholder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlaceholderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Placeholder::class);
    }

    public function getQueryBuilderByUser(User $user): QueryBuilder
    {
        return $this->createQueryBuilder('placeholder')
            ->where('placeholder.user = :user')
            ->setParameter('user', $user)
            ->addOrderBy('placeholder.key', 'asc')
            ->distinct();
    }

    public function filterByUserAndSortByTitle(User $user, Collection $collection): QueryBuilder
    {
        return $this->createQueryBuilder('placeholder')
            ->leftJoin('placeholder.collections', 'collection')
            ->leftJoin('collection.sharedCollections', 'sharedCollection')
            ->where('placeholder.user = :user')
            ->orWhere('sharedCollection.sharedWithUser = :user and sharedCollection.collection = :collection')
            ->setParameter('user', $user)
            ->setParameter('collection', $collection)
            ->addOrderBy('placeholder.key', 'asc')
            ->distinct();
    }

    public function getQueryByUser(User $user): Query
    {
        return $this
            ->createQueryBuilder('placeholder')
            ->andWhere('placeholder.user = :user')
            ->setParameter('user', $user)
            ->addOrderBy('placeholder.isFavorite', 'desc')
            ->addOrderBy('placeholder.updatedAt', 'desc')
            ->distinct()
            ->getQuery()
        ;
    }

    public function getAvailable(User $user): array
    {
        $query = $this
            ->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->addOrderBy('p.isFavorite', 'desc')
            ->addOrderBy('p.key', 'asc')
            ->getQuery()
        ;

        return $query->getResult();
    }

    public function getByUserAndCategory(User $user, Category $category)
    {
        $query = $this
            ->createQueryBuilder('placeholder')
            ->innerJoin('placeholder.categories', 'category')
            ->andWhere('placeholder.user = :user')
            ->andWhere('category.id = :category')
            ->setParameter('user', $user)
            ->setParameter('category', $category)
            ->addOrderBy('placeholder.isFavorite', 'desc')
            ->addOrderBy('placeholder.updatedAt', 'desc')
            ->getQuery()
        ;

        return $query->getResult();
    }

    public function getByUserWithoutCategory(User $user)
    {
        $query = $this
            ->createQueryBuilder('placeholder')
            ->innerJoin('placeholder.categories', 'category')
            ->andWhere('placeholder.user = :user')
            ->andWhere('category.id is null')
            ->setParameter('user', $user)
            ->addOrderBy('placeholder.isFavorite', 'desc')
            ->addOrderBy('placeholder.updatedAt', 'desc')
            ->getQuery()
        ;

        return $query->getResult();
    }

    public function getByPrompt(Prompt $prompt)
    {
        $queryBuilder = $this
            ->createQueryBuilder('p')
            ->where('p.user = :promptUser')
            ->setParameter('promptUser', $prompt->getUser())
        ;

        if (null !== $prompt->getPromptTemplate()?->getUser()) {
            $queryBuilder->orWhere('p.user = :promptTemplateUser')
                ->setParameter('promptTemplateUser', $prompt->getPromptTemplate()?->getUser())
            ;
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function save(Placeholder $placeholder): void
    {
        $this->getEntityManager()->persist($placeholder);
        $this->getEntityManager()->flush();
    }

    public function delete(Placeholder $placeholder): void
    {
        $this->getEntityManager()->remove($placeholder);
        $this->getEntityManager()->flush();
    }
}
