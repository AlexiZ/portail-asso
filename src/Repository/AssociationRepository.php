<?php

namespace App\Repository;

use App\Entity\Association;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Association>
 */
class AssociationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Association::class);
    }

    public function searchByName(string $name): array
    {
        return $this->createQueryBuilder('a')
            ->where('LOWER(a.name) LIKE :name')
            ->setParameter('name', '%'.strtolower($name).'%')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getUserSubs(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.subscriptions', 's')
            ->where('s.id = :userId')
            ->setParameter('userId', $user->getId())
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAllWithMemberships(): array
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.memberships', 'm')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAllWithoutMemberships(): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.memberships', 'm')
            ->where('m.id IS NULL')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAllWithCategory(string $category): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.categories LIKE :category')
            ->setParameter('category', '%"'.$category.'"%')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
