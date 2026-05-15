<?php

return [
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

    'discord' => [
        'client_id' => env('DISCORD_CLIENT_ID'),
        'client_secret' => env('DISCORD_CLIENT_SECRET'),
        'redirect' => env('DISCORD_REDIRECT_URI'),
    ],

    'trackmania_io' => [
        'base_url' => env('TRACKMANIA_IO_BASE_URL', 'https://trackmania.io'),
        'user_agent' => env('TRACKMANIA_IO_USER_AGENT', env('TRACKMANIA_USER_AGENT', 'tm-bot-club-sync/1.0 (contact: admin@example.com)')),
        'timeout_seconds' => (int) env('TRACKMANIA_IO_TIMEOUT_SECONDS', 10),
        'retry_times' => (int) env('TRACKMANIA_IO_RETRY_TIMES', 2),
        'retry_sleep_ms' => (int) env('TRACKMANIA_IO_RETRY_SLEEP_MS', 200),
    ],
];
