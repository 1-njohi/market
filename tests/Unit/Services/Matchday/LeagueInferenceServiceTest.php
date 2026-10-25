<?php

namespace Tests\Unit\Services\Matchday;

use App\Exceptions\UnsupportedLeagueException;
use App\Services\Matchday\LeagueInferenceService;
use Tests\TestCase;

class LeagueInferenceServiceTest extends TestCase
{
    private function f(?int $leagueId): object
    {
        return (object) ['league_id' => $leagueId];
    }

    public function test_infers_epl_when_all_fixtures_are_in_epl(): void
    {
        $svc = new LeagueInferenceService();

        $this->assertSame('EPL', $svc->infer([
            $this->f(39),
            $this->f(39),
            $this->f(39),
        ]));
    }

    public function test_infers_ucl_when_all_fixtures_are_in_ucl(): void
    {
        $svc = new LeagueInferenceService();

        $this->assertSame('UCL', $svc->infer([
            $this->f(2),
            $this->f(2),
        ]));
    }

    public function test_returns_null_when_fixtures_span_multiple_leagues(): void
    {
        $svc = new LeagueInferenceService();

        $this->assertNull($svc->infer([
            $this->f(39),
            $this->f(2),
        ]));
    }

    public function test_returns_null_for_empty_fixture_set(): void
    {
        $svc = new LeagueInferenceService();

        $this->assertNull($svc->infer([]));
    }

    public function test_ignores_null_league_ids_and_still_infers_when_one_league_remains(): void
    {
        $svc = new LeagueInferenceService();

        $this->assertSame('EPL', $svc->infer([
            $this->f(null),
            $this->f(39),
            $this->f(null),
            $this->f(39),
        ]));
    }

    public function test_throws_when_no_supported_league_matches(): void
    {
        $svc = new LeagueInferenceService();

        $this->expectException(UnsupportedLeagueException::class);

        $svc->infer([
            $this->f(999),
            $this->f(999),
        ]);
    }
}