<?php

namespace App\Exceptions;

use Carbon\CarbonInterface;
use RuntimeException;

class MatchdayLockedException extends RuntimeException
{
    public function __construct(public readonly CarbonInterface $closedAt)
    {
        parent::__construct("Matchday picks closed at {$closedAt}.");
    }
}