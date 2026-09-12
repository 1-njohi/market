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
        'key' => env('API_FOOTBALL_KEY'),
        'base_url' => env('API_FOOTBALL_BASE_URL'),
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
    ],

];
