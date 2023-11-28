<?php

namespace App\Repository;

use App\Entity\Prompt;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Prompt>
 *
 * @method Prompt|null find($id, $lockMode = null, $lockVersion = null)
 * @method Prompt|null findOneBy(array $criteria, array $orderBy = null)
 * @method Prompt[]    findAll()
 * @method Prompt[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PromptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Prompt::class);
    }

    public function getQueryLatestByUser(User $user): Query
    {
        return $this
            ->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->addOrderBy('p.updatedAt', 'desc')
            ->getQuery()
            ;
    }

    public function getLatestByUser(User $user, int $limit = 100)
    {
        return $this
            ->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->addOrderBy('p.updatedAt', 'desc')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function save(Prompt $prompt): void
    {
        $this->getEntityManager()->persist($prompt);
        $this->getEntityManager()->flush();
    }

    public function delete(Prompt $prompt): void
    {
        $this->getEntityManager()->remove($prompt);
        $this->getEntityManager()->flush();
    }
}
