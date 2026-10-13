<?php

namespace App\Tests\Feed;

use App\Feed\FeedFilters;
use PHPUnit\Framework\TestCase;

class FeedFiltersTest extends TestCase
{
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new \DateTimeImmutable('2026-10-13 12:00:00');
    }

    public function testDefaults(): void
    {
        $filters = FeedFilters::forEvents([], $this->now);

        $this->assertSame(30, $filters->limit);
        $this->assertFalse($filters->private);
        $this->assertFalse($filters->past);
        $this->assertSame([], $filters->categories);
        $this->assertSame([], $filters->associationSlugs);
        $this->assertEquals($this->now, $filters->from);
        $this->assertEquals(new \DateTimeImmutable('2027-10-13 12:00:00'), $filters->to);
        $this->assertSame(['startAt', 'ASC'], [$filters->sortField, $filters->sortDirection]);
        $this->assertSame([], $filters->errors);
    }

    public function testInvalidParametersAreIgnored(): void
    {
        $filters = FeedFilters::forEvents([
            'limit' => 'abc',
            'private' => 'yes',
            'from' => '2026-02-30',
            'tags' => 'unknown',
            'sort' => 'foo,DESC',
            'association' => ['array'],
        ], $this->now);

        $this->assertSame(30, $filters->limit);
        $this->assertFalse($filters->private);
        $this->assertEquals($this->now, $filters->from);
        $this->assertSame([], $filters->categories);
        $this->assertSame([], $filters->associationSlugs);
        $this->assertSame(['startAt', 'ASC'], [$filters->sortField, $filters->sortDirection]);
        $this->assertCount(6, $filters->errors);
    }

    public function testValidParameters(): void
    {
        $filters = FeedFilters::forEvents([
            'limit' => '-1',
            'private' => 'true',
            'tags' => 'animals, unknown',
            'categories' => 'arts-culture,animals',
            'association' => 'asso-a,asso-b,,asso-a',
        ], $this->now);

        $this->assertSame(-1, $filters->limit);
        $this->assertTrue($filters->private);
        $this->assertSame(['animals', 'arts-culture'], $filters->categories);
        $this->assertSame(['asso-a', 'asso-b'], $filters->associationSlugs);
        $this->assertCount(1, $filters->errors);
    }

    public function testPastFromCannotBypassFuture(): void
    {
        $filters = FeedFilters::forEvents(['from' => '2020-01-01'], $this->now);

        $this->assertEquals($this->now, $filters->from);
    }

    public function testFutureFromShiftsDefaultEnd(): void
    {
        $filters = FeedFilters::forEvents(['from' => '2027-01-01'], $this->now);

        $this->assertEquals(new \DateTimeImmutable('2027-01-01 00:00:00'), $filters->from);
        $this->assertEquals(new \DateTimeImmutable('2028-01-01 00:00:00'), $filters->to);
    }

    public function testPastDefaults(): void
    {
        $filters = FeedFilters::forEvents(['past' => 'true'], $this->now);

        $this->assertEquals(new \DateTimeImmutable('2025-10-13 12:00:00'), $filters->from);
        $this->assertEquals(new \DateTimeImmutable('2027-10-13 12:00:00'), $filters->to);
        $this->assertSame(['startAt', 'DESC'], [$filters->sortField, $filters->sortDirection]);
    }

    public function testPastWithExplicitBoundsAndSort(): void
    {
        $filters = FeedFilters::forEvents(['past' => '1', 'from' => '2020-01-01', 'to' => '2020-12-31', 'sort' => 'startAt,asc'], $this->now);

        $this->assertEquals(new \DateTimeImmutable('2020-01-01 00:00:00'), $filters->from);
        $this->assertEquals(new \DateTimeImmutable('2020-12-31 23:59:59'), $filters->to);
        $this->assertSame(['startAt', 'ASC'], [$filters->sortField, $filters->sortDirection]);
    }

    public function testSortDirectionDefaultsToAsc(): void
    {
        $errors = [];

        $this->assertSame(['name', 'ASC'], FeedFilters::parseSort(['sort' => 'name'], FeedFilters::ASSOCIATION_SORT_FIELDS, ['updatedAt', 'DESC'], $errors));
        $this->assertSame(['updatedAt', 'DESC'], FeedFilters::parseSort(['sort' => 'name,UP'], FeedFilters::ASSOCIATION_SORT_FIELDS, ['updatedAt', 'DESC'], $errors));
        $this->assertCount(1, $errors);
    }
}
