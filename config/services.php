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

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.8-flash'),
        'fallback_models' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('GEMINI_FALLBACK_MODELS', 'gemini-3.7-flash,gemini-3.6-flash,gemini-3.5-flash')),
        ))),
        'retry_rounds' => (int) env('GEMINI_RETRY_ROUNDS', 3),
        'timeout' => (int) env('GEMINI_TIMEOUT', 240),
        'max_output_tokens' => (int) env('GEMINI_MAX_OUTPUT_TOKENS', 16384),
        'google_search' => (bool) env('GEMINI_GOOGLE_SEARCH', false),
        'max_inline_image_bytes' => 10 * 1024 * 1024,
    ],

    /*
    | Có key thì dùng Open Charge Map. Không có key tự chuyển sang
    | OpenStreetMap qua Overpass, vẫn hoàn toàn miễn phí.
    */
    'open_charge_map' => [
        'key' => env('OPEN_CHARGE_MAP_API_KEY'),
        'url' => env('OPEN_CHARGE_MAP_API_URL', 'https://api.openchargemap.io/v3'),
        'country_code' => env('OPEN_CHARGE_MAP_COUNTRY_CODE', 'VN'),
        'distance_km' => (int) env('OPEN_CHARGE_MAP_DISTANCE_KM', 80),
        'max_results' => (int) env('OPEN_CHARGE_MAP_MAX_RESULTS', 12),
        'cache_seconds' => (int) env('OPEN_CHARGE_MAP_CACHE_SECONDS', 21600),
        'timeout' => (int) env('OPEN_CHARGE_MAP_TIMEOUT', 10),
    ],

    'overpass' => [
        'url' => env('OVERPASS_API_URL', 'https://overpass-api.de/api/interpreter'),
        'distance_km' => (int) env('OVERPASS_DISTANCE_KM', 80),
        'max_results' => (int) env('OVERPASS_MAX_RESULTS', 12),
        'cache_seconds' => (int) env('OVERPASS_CACHE_SECONDS', 21600),
        'timeout' => (int) env('OVERPASS_TIMEOUT', 25),
        'query_timeout' => (int) env('OVERPASS_QUERY_TIMEOUT', 20),
    ],

    'nominatim' => [
        'url' => env('NOMINATIM_URL', 'https://nominatim.openstreetmap.org/search'),
        'user_agent' => env(
            'NOMINATIM_USER_AGENT',
            env('APP_NAME', 'Cars').' station finder ('.env('APP_URL', 'http://localhost').')',
        ),
        'cache_seconds' => (int) env('NOMINATIM_CACHE_SECONDS', 2592000),
        'timeout' => (int) env('NOMINATIM_TIMEOUT', 8),
    ],

    'photon' => [
        'url' => env('PHOTON_URL', 'https://photon.komoot.io/api/'),
        'timeout' => (int) env('PHOTON_TIMEOUT', 10),
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

];
