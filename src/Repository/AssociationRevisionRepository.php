<?php

namespace App\Repository;

use App\Entity\AssociationRevision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AssociationRevision>
 */
class AssociationRevisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AssociationRevision::class);
    }

    /**
     * @return Paginator<AssociationRevision>
     */
    public function findPendingPaginated(?int $associationId, ?\DateTimeInterface $from, ?\DateTimeInterface $to, ?string $search, int $page, int $perPage): Paginator
    {
        $qb = $this->createQueryBuilder('r')
            ->addSelect('a')
            ->join('r.association', 'a')
            ->where('r.approved = false')
            ->orderBy('r.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        if ($associationId) {
            $qb->andWhere('a.id = :associationId')->setParameter('associationId', $associationId);
        }
        if ($from) {
            $qb->andWhere('r.createdAt >= :from')->setParameter('from', $from);
        }
        if ($to) {
            $qb->andWhere('r.createdAt < :to')->setParameter('to', $to);
        }
        if ($search) {
            $qb->andWhere('a.name LIKE :search OR r.createdBy LIKE :search')
                ->setParameter('search', '%'.addcslashes($search, '%_\\').'%');
        }

        return new Paginator($qb);
    }

    /**
     * Associations having at least one pending revision, for the filter select.
     *
     * @return array<array{id: int, name: string}>
     */
    public function findAssociationsWithPending(): array
    {
        return $this->createQueryBuilder('r')
            ->select('DISTINCT a.id, a.name')
            ->join('r.association', 'a')
            ->where('r.approved = false')
            ->orderBy('a.name')
            ->getQuery()
            ->getArrayResult();
    }
}
