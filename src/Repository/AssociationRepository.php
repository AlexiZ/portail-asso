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
            ->innerJoin('a.subscribers', 's')
            ->where('s.id = :userId')
            ->setParameter('userId', $user->getId())
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
