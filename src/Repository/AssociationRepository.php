<?php

namespace App\Repository;

use App\Entity\Association;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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
            ->where('s.user = :user')
            ->setParameter('user', $user)
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

    /**
     * @param string[] $categories AssociationCategory values
     */
    public function findForFeed(array $categories, string $sortField, string $sortDirection): array
    {
        $qb = $this->createQueryBuilder('a')->orderBy('a.'.$sortField, $sortDirection);

        return self::andWhereAnyCategory($qb, 'a', $categories)->getQuery()->getResult();
    }

    /**
     * Keeps associations having at least one of the categories (no-op when empty).
     *
     * @param string[] $categories AssociationCategory values
     */
    public static function andWhereAnyCategory(QueryBuilder $qb, string $alias, array $categories): QueryBuilder
    {
        if (!$categories) {
            return $qb;
        }

        $or = $qb->expr()->orX();
        foreach (array_values($categories) as $i => $category) {
            $or->add(sprintf('%s.categories LIKE :feedCategory%d', $alias, $i));
            $qb->setParameter('feedCategory'.$i, '%"'.$category.'"%');
        }

        return $qb->andWhere($or);
    }
}
