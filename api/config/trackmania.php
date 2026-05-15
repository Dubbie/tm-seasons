<?php

return [
    'auth_base_url' => env('TRACKMANIA_AUTH_BASE_URL', 'https://prod.trackmania.core.nadeo.online'),
    'base_url' => env('TRACKMANIA_BASE_URL', 'https://live-services.trackmania.nadeo.live'),
    'dedicated_login' => env('TRACKMANIA_DEDICATED_LOGIN'),
    'dedicated_password' => env('TRACKMANIA_DEDICATED_PASSWORD'),
    'user_agent' => env('TRACKMANIA_USER_AGENT', 'tm-bot/1.0 (+https://example.com)'),
    'audience' => env('TRACKMANIA_AUDIENCE', 'NadeoLiveServices'),
    'token_cache_key' => env('TRACKMANIA_TOKEN_CACHE_KEY', 'trackmania.token'),
    'token_cache_ttl_fallback' => (int) env('TRACKMANIA_TOKEN_CACHE_TTL_FALLBACK', 900),
    'token_expiry_skew_seconds' => (int) env('TRACKMANIA_TOKEN_EXPIRY_SKEW_SECONDS', 60),
    'timeout_seconds' => (int) env('TRACKMANIA_TIMEOUT_SECONDS', 10),
    'retry_times' => (int) env('TRACKMANIA_RETRY_TIMES', 3),
    'retry_sleep_ms' => (int) env('TRACKMANIA_RETRY_SLEEP_MS', 200),
];
