<?php

namespace Tests\Unit\Services\Matchday;

use App\Services\Matchday\MatchdayLockCalculator;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class MatchdayLockCalculatorTest extends TestCase
{
    private function f(string $iso, ?int $leagueId = 39): object
    {
        return (object) [
            'league_id' => $leagueId,
            'date'      => $iso,
        ];
    }

    public function test_lock_time_is_first_kickoff_minus_offset_minutes(): void
    {
        config()->set('marketplace.matchday.lock_offset_minutes', 10);

        $calc = new MatchdayLockCalculator();

        $result = $calc->lockTime([
            $this->f('2026-08-15T15:00:00Z'),
        ]);

        $this->assertInstanceOf(CarbonImmutable::class, $result);
        $this->assertSame('2026-08-15 14:50:00', $result->utc()->format('Y-m-d H:i:s'));
    }

    public function test_offset_reads_from_config(): void
    {
        config()->set('marketplace.matchday.lock_offset_minutes', 25);

        $calc = new MatchdayLockCalculator();

        $result = $calc->lockTime([
            $this->f('2026-08-15T15:00:00Z'),
        ]);

        $this->assertSame('2026-08-15 14:35:00', $result->utc()->format('Y-m-d H:i:s'));
    }

    public function test_returns_null_when_no_fixtures(): void
    {
        $calc = new MatchdayLockCalculator();

        $this->assertNull($calc->lockTime([]));
    }

    public function test_uses_earliest_kickoff_when_fixtures_are_out_of_order(): void
    {
        config()->set('marketplace.matchday.lock_offset_minutes', 10);

        $calc = new MatchdayLockCalculator();

        $result = $calc->lockTime([
            $this->f('2026-08-15T17:30:00Z'),
            $this->f('2026-08-15T15:00:00Z'),
            $this->f('2026-08-15T20:00:00Z'),
        ]);

        $this->assertSame('2026-08-15 14:50:00', $result->utc()->format('Y-m-d H:i:s'));
    }
}