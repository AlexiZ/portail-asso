<?php

namespace App\Repository;

use App\Entity\Event;
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
                $eventStart = $event->getStartAt();

                foreach ($rule->getOccurrencesBetween($from, $to) as $date) {
                    $dateWithTime = (clone $date)->setTime(
                        (int) $eventStart->format('H'),
                        (int) $eventStart->format('i'),
                        (int) $eventStart->format('s')
                    );
                    $nextEvent = clone $event;
                    $nextEvent->setStartAt($dateWithTime);

                    $eventsWithOccurrences[] = $nextEvent;
                }
            } elseif ($event->getStartAt() >= $from && $event->getStartAt() <= $to) {
                $eventsWithOccurrences[] = $event;
            }
        }

        usort($eventsWithOccurrences, fn ($a, $b) => $a->getStartAt() <=> $b->getStartAt());

        return $eventsWithOccurrences;
    }
}
