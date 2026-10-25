<?php

namespace App\Services\Matchday;

use Carbon\CarbonImmutable;

class MatchdayLockCalculator
{
    /**
     * Lock time = earliest fixture kickoff minus the configured offset.
     *
     * @param  iterable<object>  $fixtures  objects exposing a `date`
     */
    public function lockTime(iterable $fixtures): ?CarbonImmutable
    {
        $earliest = null;

        foreach ($fixtures as $fixture) {
            $raw = $fixture->date ?? null;
            if ($raw === null) {
                continue;
            }

            $when = $raw instanceof CarbonImmutable
                ? $raw
                : CarbonImmutable::parse($raw);

            if ($earliest === null || $when->lt($earliest)) {
                $earliest = $when;
            }
        }

        if ($earliest === null) {
            return null;
        }

        $offset = (int) config('marketplace.matchday.lock_offset_minutes');

        return $earliest->subMinutes($offset);
    }
}