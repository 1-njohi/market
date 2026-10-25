<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'api_sports' => [
        'key' => env('API_SPORTS_KEY'),
        'base_url' => env('API_SPORTS_BASE_URL'),

        'upcoming_days'          => (int) env('API_SPORTS_UPCOMING_DAYS', 14),
        'live_lookback_hours'    => (int) env('API_SPORTS_LIVE_LOOKBACK_HOURS', 3),
        'live_lookahead_minutes' => (int) env('API_SPORTS_LIVE_LOOKAHEAD_MINUTES', 15),
        'sleep_between_requests' => (int) env('API_SPORTS_SLEEP_BETWEEN_REQUESTS', 1),
    ],

    'mock_api' => [
        'enabled' => env('MOCK_FOOTBALL_ENABLED', false),
        'secret'  => env('MOCK_FOOTBALL_SECRET', ''),
    ],

    'betslip_pirates' => [
        'platform_user_email' => env('PLATFORM_USER_EMAIL', 'platform@betslip-pirates.com'),

        // Ordered from lowest to highest tier.
        // The last tier has no `max` — it's the default for anyone exceeding prior thresholds.
        'fee_tiers' => [
            [
                'max' => (int) env('PLATFORM_FEE_TIER_1_MAX', 9),
                'percentage' => (float) env('PLATFORM_FEE_TIER_1_PERCENTAGE', 0.25),
            ],
            [
                'max' => (int) env('PLATFORM_FEE_TIER_2_MAX', 49),
                'percentage' => (float) env('PLATFORM_FEE_TIER_2_PERCENTAGE', 0.20),
            ],
            [
                'max' => (int) env('PLATFORM_FEE_TIER_3_MAX', 99),
                'percentage' => (float) env('PLATFORM_FEE_TIER_3_PERCENTAGE', 0.15),
            ],
            [
                'max' => null, // highest tier — no cap
                'percentage' => (float) env('PLATFORM_FEE_TIER_4_PERCENTAGE', 0.10),
            ],
        ],
        'referral_defaults' => [
            'reward_percentage' => (float) env('REFERRAL_REWARD_PCT', 0.10),
            'max_transactions' => (int) env('REFERRAL_MAX_TRANSACTIONS', 20),
            'window_months' => (int) env('REFERRAL_WINDOW_MONTHS', 12),
            'referee_discount_pct' => (float) env('REFERRAL_REFEREE_DISCOUNT_PCT', 0.20),
            'referee_discount_cap' => (float) env('REFERRAL_REFEREE_DISCOUNT_CAP', 20.00),
        ]
    ]
];
