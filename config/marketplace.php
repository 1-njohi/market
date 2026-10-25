<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Seller eligibility thresholds
    |--------------------------------------------------------------------------
    |
    | A user's "pick win rate" is computed across the rolling window below,
    | counting only their betslip picks (one row in betslip_odd). Contest
    | picks are deliberately excluded — contests reward a different shape
    | of decision than selling.
    |
    | - min_settled_picks   : minimum sample size before a rate is meaningful.
    | - min_win_rate_to_sell: rate required to newly qualify as a seller.
    | - min_win_rate_to_stay: higher-of-the-two hysteresis — a seller keeps
    |                         selling until their rate drops below this.
    | - grace_period_days   : how long a warned seller has to recover before
    |                         delisting.
    | - rolling_window_days : how far back "rolling" reaches.
    |
    */

    'min_settled_picks' => (int) env('MARKETPLACE_MIN_SETTLED_PICKS', 20),
    'min_win_rate_to_sell' => (float) env('MARKETPLACE_MIN_WIN_RATE_TO_SELL', 0.55),
    'min_win_rate_to_stay' => (float) env('MARKETPLACE_MIN_WIN_RATE_TO_STAY', 0.50),
    'grace_period_days' => (int) env('MARKETPLACE_GRACE_PERIOD_DAYS', 30),
    'rolling_window_days' => (int) env('MARKETPLACE_ROLLING_WINDOW_DAYS', 90),

    'matchday' => [
        'seasons' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('MATCHDAY_SEASONS', ''))
        ))),
        'lock_offset_minutes' => (int) env('MATCHDAY_LOCK_OFFSET_MINUTES', 10),
        'max_sub_league_members' => (int) env('MATCHDAY_MAX_SUB_LEAGUE_MEMBERS', 20),
        'supported_league_codes' => [
            'EPL' => 39,
            'UCL' => 2,
        ],
        'league_labels' => [
            'EPL' => 'Premier League',
            'UCL' => 'UEFA Champions League',
            'LALIGA' => 'LaLiga',
        ],
    ],
    'crowd_wisdom' => [
        // Minimum total predictions per (fixture, market) before the
        // distribution is visible at all. Below this, users see a
        // "waiting for N votes" message instead of any percentages.
        'min_threshold' => (int) env('CROWD_WISDOM_MIN_THRESHOLD', 50),

        // Selectable percentile bands for the "Top X%" view. Each value is
        // an integer percentage (5 = top 5%). Users pick one of these from
        // a dropdown.
        'top_percentiles' => array_values(array_filter(array_map(
            fn($v) => (int) trim($v),
            explode(',', (string) env('CROWD_WISDOM_TOP_PERCENTILES', '5,10,25,50'))
        ))),
    ],
];