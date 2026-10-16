<?php

namespace App\Tests\Entity;

use App\Entity\Event;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    private function event(string $startAt, ?string $endAt = null, ?string $rule = null): Event
    {
        return (new Event())
            ->setStartAt(new \DateTime($startAt))
            ->setEndAt($endAt ? new \DateTime($endAt) : null)
            ->setRecurrenceRule($rule)
        ;
    }

    public function testEffectiveEndAt(): void
    {
        $this->assertEquals(new \DateTime('2026-10-20 18:00'), $this->event('2026-10-20 10:00', '2026-10-20 18:00')->getEffectiveEndAt());
        $this->assertEquals(new \DateTime('2026-10-20 23:59:59'), $this->event('2026-10-20 10:00')->getEffectiveEndAt());
        $this->assertEquals(new \DateTime('2026-10-21 00:00:59'), $this->event('2026-10-20 23:59:59')->getEffectiveEndAt());
    }

    public function testWithStartDateKeepsTimeAndDuration(): void
    {
        $event = $this->event('2026-10-20 10:00', '2026-10-21 12:30');
        $occurrence = $event->withStartDate(new \DateTime('2026-11-03'));

        $this->assertEquals(new \DateTime('2026-11-03 10:00'), $occurrence->getStartAt());
        $this->assertEquals(new \DateTime('2026-11-04 12:30'), $occurrence->getEndAt());
        $this->assertEquals(new \DateTime('2026-10-20 10:00'), $event->getStartAt());
    }

    public function testOccurrenceOnSingleEvent(): void
    {
        $event = $this->event('2026-10-20 10:00');

        $this->assertSame($event, $event->occurrenceOn(new \DateTimeImmutable('2026-10-20')));
        $this->assertNull($event->occurrenceOn(new \DateTimeImmutable('2026-10-21')));
    }

    public function testOccurrenceOnRecurringEvent(): void
    {
        $event = $this->event('2026-10-20 10:00', '2026-10-20 12:00', "DTSTART:20261020T100000\nRRULE:FREQ=WEEKLY;COUNT=5");

        $occurrence = $event->occurrenceOn(new \DateTimeImmutable('2026-10-27'));
        $this->assertEquals(new \DateTime('2026-10-27 10:00'), $occurrence->getStartAt());
        $this->assertEquals(new \DateTime('2026-10-27 12:00'), $occurrence->getEndAt());

        $this->assertNull($event->occurrenceOn(new \DateTimeImmutable('2026-10-28')));
        $this->assertNull($event->occurrenceOn(new \DateTimeImmutable('2026-12-29')));
    }
}
