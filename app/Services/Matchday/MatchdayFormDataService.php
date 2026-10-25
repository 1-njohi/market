<?php

namespace App\Services\Matchday;

use App\Models\Contest;
use App\Models\Fixture;
use App\Models\League;

/**
 * Read-side data for the admin matchday forms.
 *
 * Kept separate from the write-side (MatchdayContestBuilder) so the form
 * can be shaped without touching creation logic.
 */
class MatchdayFormDataService
{
    /**
     * How many days ahead to look for candidate fixtures. The admin
     * creates matchdays roughly a week in advance.
     */
    public const FIXTURE_WINDOW_DAYS = 7;

    /**
     * @return array<int, string>
     */
    public function seasons(): array
    {
        return array_values((array) config('marketplace.matchday.seasons', []));
    }

    /**
     * Supported leagues with human-readable names for the dropdown.
     * League name lookup is by `leagues.id` (what `fixtures.league_id`
     * references).
     *
     * @return array<int, array{code:string, league_id:int, name:string}>
     */
    public function leagues(): array
    {
        $supported = (array) config('marketplace.matchday.supported_league_codes', []);

        $names = League::whereIn('id', array_values($supported))
            ->pluck('name', 'id');

        return collect($supported)->map(fn ($id, $code) => [
            'code'      => (string) $code,
            'league_id' => (int) $id,
            'name'      => (string) ($names[(int) $id] ?? $code),
        ])->values()->all();
    }

    /**
     * Fixtures grouped by league code, filtered to:
     *   - supported leagues only
     *   - kickoff within the window
     *   - NS (not started)
     *
     * @return array<string, array<int, array{id:int, home_team:string, away_team:string, kickoff:?string}>>
     */
    public function fixturesByLeague(): array
    {
        $supported = (array) config('marketplace.matchday.supported_league_codes', []);
        $byId = [];
        foreach ($supported as $code => $id) {
            $byId[(int) $id] = (string) $code;
        }

        $out = [];
        foreach (array_keys($byId) as $_ => $__) { /* noop */ }
        foreach ($supported as $code => $_) {
            $out[(string) $code] = [];
        }

        if (empty($byId)) {
            return $out;
        }

        $from = now();
        $to   = now()->addDays(self::FIXTURE_WINDOW_DAYS);

        $fixtures = Fixture::with(['homeTeam', 'awayTeam'])
            ->whereIn('league_id', array_keys($byId))
            ->whereBetween('date', [$from, $to])
            ->where('status_short', 'NS')
            ->orderBy('date')
            ->get();

        foreach ($fixtures as $fixture) {
            $code = $byId[(int) $fixture->league_id] ?? null;
            if ($code === null) {
                continue;
            }

            $out[$code][] = [
                'id'        => (int) $fixture->id,
                'home_team' => $fixture->homeTeam?->name ?? 'Unknown',
                'away_team' => $fixture->awayTeam?->name ?? 'Unknown',
                'kickoff'   => $fixture->date?->toISOString(),
            ];
        }

        return $out;
    }

    /**
     * Suggested next matchday number per (season, league_code) pair.
     * Keyed by "season:code" so the frontend can index without extra
     * lookups when season or league changes.
     *
     * Gaps are permitted — the admin's numbering is authoritative.
     *
     * @return array<string, int>
     */
    public function suggestedMatchdayNumbers(): array
    {
        $out = [];

        foreach ($this->seasons() as $season) {
            foreach ($this->leagues() as $league) {
                $max = (int) Contest::where('is_matchday', true)
                    ->where('season', $season)
                    ->where('league_code', $league['code'])
                    ->max('matchday_number');

                $out["{$season}:{$league['code']}"] = $max + 1;
            }
        }

        return $out;
    }

    /**
     * All matchdays for the admin index, cancelled included.
     *
     * @return array<int, array<string, mixed>>
     */
    public function existingMatchdays(): array
    {
        return Contest::where('is_matchday', true)
            ->withCount([
                'entries as entries_count' => fn ($q) => $q->where('status', 'accepted'),
            ])
            ->orderByDesc('season')
            ->orderBy('league_code')
            ->orderByDesc('matchday_number')
            ->get()
            ->map(fn (Contest $c) => [
                'id'              => (int) $c->id,
                'uuid'            => $c->uuid,
                'name'            => $c->name,
                'season'          => $c->season,
                'league_code'     => $c->league_code,
                'matchday_number' => (int) $c->matchday_number,
                'locks_at'        => $c->locks_at?->toISOString(),
                'status'          => $c->status,
                'entries_count'   => (int) $c->entries_count,
                'created_at'      => $c->created_at->toISOString(),
            ])
            ->values()
            ->all();
    }
}