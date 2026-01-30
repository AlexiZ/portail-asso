<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $user, bool $flush = false): void
    {
        $this->getEntityManager()->persist($user);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function textualSearch(string $term)
    {
        return $this->createQueryBuilder('u')
            ->where('u.firstname LIKE :q OR u.lastname LIKE :q OR u.email LIKE :q')
            ->setParameter('q', '%'.$term.'%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    public function withSubscriptions(): array
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.subscriptions', 's')
            ->where('u.activateWeeklyReport = 1')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAdmins(int $limit = 0): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('JSON_CONTAINS(u.roles, :admin) = 1')
            ->setParameter('admin', json_encode('ROLE_ADMIN'))
        ;
        if ($limit > 0) {
            $qb->setMaxResults($limit);
        }

        return $qb
            ->getQuery()
            ->getResult()
        ;
    }
}
