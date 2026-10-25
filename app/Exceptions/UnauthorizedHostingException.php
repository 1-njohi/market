<?php

namespace App\Exceptions;

use RuntimeException;

class UnauthorizedHostingException extends RuntimeException
{
    /**
     * Build the exception with a message interpolated from the current
     * marketplace config. Callers should not construct messages
     * themselves — that's how magic numbers drift into error text.
     */
    public static function fromConfig(): self
    {
        return new self(sprintf(
            'You must have at least %d settled picks and a %.0f%% win rate over the last %d days to host a contest.',
            (int) config('marketplace.min_settled_picks'),
            (float) config('marketplace.min_win_rate_to_sell') * 100,
            (int) config('marketplace.rolling_window_days'),
        ));
    }
}