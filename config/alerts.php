<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Master switch
    |--------------------------------------------------------------------------
    | Keep false locally so nothing fires during development. Set
    | ALERT_ENABLED=true in staging/production.
    */
    'enabled' => env('ALERT_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Slack
    |--------------------------------------------------------------------------
    | Create an incoming webhook at api.slack.com/apps → Incoming Webhooks.
    | The URL is per-channel; if you want alerts in #alerts, post to that
    | channel's webhook.
    */
    'slack' => [
        'enabled' => env('ALERT_SLACK_ENABLED', false),
        'webhook' => env('ALERT_SLACK_WEBHOOK'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Telegram
    |--------------------------------------------------------------------------
    | Create a bot with @BotFather, add it to a group, then message the group
    | once and hit https://api.telegram.org/bot<TOKEN>/getUpdates to find the
    | chat_id (a negative number for groups).
    */
    'telegram' => [
        'enabled' => env('ALERT_TELEGRAM_ENABLED', false),
        'bot_token' => env('ALERT_TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('ALERT_TELEGRAM_CHAT_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS (Africa's Talking)
    |--------------------------------------------------------------------------
    | Disabled by default. To enable, set ALERT_SMS_ENABLED=true and provide
    | credentials. The live endpoint is used when APP_ENV=production; the
    | sandbox otherwise.
    |
    | `only_on_severity` restricts SMS to a single tier so you don't burn
    | credits on warns. Valid: 'fail', 'warn', 'recovered'.
    */
    'sms' => [
        'enabled' => env('ALERT_SMS_ENABLED', false),
        'username' => env('AFRICASTALKING_USERNAME'),
        'api_key' => env('AFRICASTALKING_API_KEY'),
        'from' => env('AFRICASTALKING_FROM'),
        'recipients' => array_values(array_filter(
            array_map('trim', explode(',', env('ALERT_SMS_RECIPIENTS', '')))
        )),
        'only_on_severity' => env('ALERT_SMS_ONLY_ON', 'fail'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email
    |--------------------------------------------------------------------------
    | Used for digests and for fail-severity alerts. Comma-separated list.
    */
    'mail' => [
        'enabled' => env('ALERT_MAIL_ENABLED', false),
        'recipients' => array_values(array_filter(
            array_map('trim', explode(',', env('ALERT_MAIL_RECIPIENTS', '')))
        )),
    ],

    /*
    |--------------------------------------------------------------------------
    | Re-alert cadence
    |--------------------------------------------------------------------------
    | How many minutes to wait before re-alerting on a sustained failure.
    | State-change alerts are immediate regardless of this value.
    */
    're_alert_minutes' => (int) env('ALERT_RE_ALERT_MINUTES', 30),

    /*
    |--------------------------------------------------------------------------
    | Dashboard link
    |--------------------------------------------------------------------------
    | Included in every alert so the responder can click straight through.
    */
    'dashboard_url' => rtrim(env('APP_URL', 'http://localhost'), '/') . '/admin/health',
];
