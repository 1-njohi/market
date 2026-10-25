<?php

namespace App\Services\Matchday;

use App\Exceptions\UnsupportedLeagueException;
use App\Models\Contest;
use App\Models\Fixture;
use Illuminate\Validation\ValidationException;

class MatchdayContestBuilder
{
    public function __construct(
        protected LeagueInferenceService $inference,
        protected MatchdayLockCalculator $lockCalculator,
    ) {
    }

    /**
     * Validate and stamp a provisional matchday contest with league_code,
     * locks_at, and entry_deadline_at. Throws ValidationException on any
     * invariant violation so the HTTP layer returns a clean 422.
     *
     * @param  array<int>  $fixtureIds
     * @throws ValidationException
     */
    public function hydrate(Contest $contest, array $fixtureIds): void
    {
        if (!$contest->is_matchday) {
            return;
        }

        $fixtures = Fixture::whereIn('id', $fixtureIds)->get();

        // 1. Season must come from the configured list.
        $seasons = (array) config('marketplace.matchday.seasons', []);
        if (!in_array((string) $contest->season, $seasons, true)) {
            throw ValidationException::withMessages([
                'season' => 'The selected season is not available.',
            ]);
        }

        // 2. League inference.
        try {
            $leagueCode = $this->inference->infer($fixtures);
        } catch (UnsupportedLeagueException $e) {
            throw ValidationException::withMessages([
                'legs' => 'That league is not supported for matchday contests.',
            ]);
        }

        if ($leagueCode === null) {
            throw ValidationException::withMessages([
                'legs' => 'Matchday contests cannot mix fixtures from multiple leagues.',
            ]);
        }

        // 3. Lock time.
        $lock = $this->lockCalculator->lockTime($fixtures);
        if ($lock === null) {
            throw ValidationException::withMessages([
                'legs' => 'Matchday contests require at least one fixture.',
            ]);
        }

        // 4. Duplicate (season, league_code, matchday_number) guard.
        $exists = Contest::where('is_matchday', true)
            ->where('season', $contest->season)
            ->where('league_code', $leagueCode)
            ->where('matchday_number', $contest->matchday_number)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'matchday_number' => 'That matchday already exists for this season and league.',
            ]);
        }

        // 5. Stamp the authoritative fields.
        $contest->league_code = $leagueCode;
        $contest->locks_at = $lock;
        $contest->entry_deadline_at = $lock;
    }
}