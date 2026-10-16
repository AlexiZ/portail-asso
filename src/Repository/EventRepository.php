<?php

namespace App\Repository;

use App\Entity\Association;
use App\Entity\Event;
use App\Feed\FeedFilters;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use RRule\RRule;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function getNearestEvents(): array
    {
        $from = new \DateTimeImmutable();
        $to = $from->modify('+30 days');

        $events = $this->getEventsWithPotentialOccurrences($to);

        return $this->getOccurrences($events, $from, $to);
    }

    /**
     * @param Association[] $associations restricts to these associations when not empty
     *
     * @return Event[] occurrences (recurring events expanded), sorted then limited
     */
    public function findForFeed(FeedFilters $filters, array $associations): array
    {
        $qb = $this->createQueryBuilder('e')
            ->join('e.association', 'a')
            ->andWhere('e.startAt <= :to')
            ->setParameter('to', $filters->to)
        ;
        if (!$filters->private) {
            $qb->andWhere('e.isPublic = true');
        }
        if ($associations) {
            $qb->andWhere('a IN (:associations)')->setParameter('associations', $associations);
        }
        AssociationRepository::andWhereAnyCategory($qb, 'a', $filters->categories);

        $events = $this->getOccurrences($qb->getQuery()->getResult(), $filters->from, $filters->to);

        $getter = 'get'.ucfirst($filters->sortField);
        $direction = 'DESC' === $filters->sortDirection ? -1 : 1;
        usort($events, fn (Event $a, Event $b) => $direction * (($a->$getter() <=> $b->$getter()) ?: ($a->getStartAt() <=> $b->getStartAt())));

        return -1 === $filters->limit ? $events : array_slice($events, 0, $filters->limit);
    }

    protected function getEventsWithPotentialOccurrences(\DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.isPublic = true')
            ->andWhere('e.startAt <= :to')
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult()
        ;
    }

    protected function getOccurrences(array $events, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $eventsWithOccurrences = [];

        foreach ($events as $event) {
            if ($event->getRecurrenceRule()) {
                $rule = new RRule($event->getRecurrenceRule());

                foreach ($rule->getOccurrencesBetween($from, $to) as $date) {
                    $eventsWithOccurrences[] = $event->withStartDate($date);
                }
            } elseif ($event->getStartAt() >= $from && $event->getStartAt() <= $to) {
                $eventsWithOccurrences[] = $event;
            }
        }

        usort($eventsWithOccurrences, fn ($a, $b) => $a->getStartAt() <=> $b->getStartAt());

        return $eventsWithOccurrences;
    }
}
