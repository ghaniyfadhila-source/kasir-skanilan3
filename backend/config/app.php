<?php

return [

    'app_name' => 'Kasir Cashless Skanilan',
    'app_url' => env('APP_URL', 'http://localhost:8000'),

    'database' => [
        'url' => env('DATABASE_URL'),
    ],

    'supabase' => [
        'url' => env('SUPABASE_URL'),
        'key' => env('SUPABASE_KEY'),
    ],

    'reverb' => [
        'app_id' => env('REVERB_APP_ID'),
        'app_key' => env('REVERB_APP_KEY'),
        'app_secret' => env('REVERB_APP_SECRET'),
        'host' => env('REVERB_HOST', 'localhost'),
        'port' => env('REVERB_PORT', 8080),
        'scheme' => env('REVERB_SCHEME', 'http'),
    ],

    'sanctum' => [
        'stateful_domains' => env('SANCTUM_STATEFUL_DOMAINS', 'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1'),
    ],

];
