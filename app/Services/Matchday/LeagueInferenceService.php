<?php

namespace App\Services\Matchday;

use App\Exceptions\UnsupportedLeagueException;

class LeagueInferenceService
{
    /**
     * Inspect the fixtures' league_id values and return a single supported
     * league code, or null if the set is empty or spans more than one league.
     *
     * @param  iterable<object>  $fixtures  objects exposing a `league_id`
     * @throws UnsupportedLeagueException  when exactly one distinct
     *                                     (unsupported) league_id is present
     */
    public function infer(iterable $fixtures): ?string
    {
        $supported = (array) config('marketplace.matchday.supported_league_codes', []);

        $byId = [];
        foreach ($supported as $code => $id) {
            $byId[(int) $id] = (string) $code;
        }

        $distinctRawIds = [];
        $distinctCodes  = [];

        foreach ($fixtures as $fixture) {
            $raw = $fixture->league_id ?? null;
            if ($raw === null) {
                continue;
            }

            $id = (int) $raw;
            $distinctRawIds[$id] = true;

            if (isset($byId[$id])) {
                $distinctCodes[$byId[$id]] = true;
            }
        }

        $supportedCount = count($distinctCodes);

        if ($supportedCount === 1) {
            return array_key_first($distinctCodes);
        }

        if ($supportedCount > 1) {
            return null;
        }

        if (count($distinctRawIds) === 1) {
            throw new UnsupportedLeagueException(
                'League id ' . array_key_first($distinctRawIds)
                . ' is not supported for matchday contests.'
            );
        }

        return null;
    }
}