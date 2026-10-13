<?php

return [

    'broadcasting' => [
        'default' => env('BROADCAST_DRIVER', 'reverb'),
        'connections' => [
            'reverb' => [
                'driver' => 'reverb',
                'key' => env('REVERB_APP_KEY'),
                'secret' => env('REVERB_APP_SECRET'),
                'app_id' => env('REVERB_APP_ID'),
                'options' => [
                    'host' => env('REVERB_HOST', 'localhost'),
                    'port' => env('REVERB_PORT', 8080),
                    'scheme' => env('REVERB_SCHEME', 'http'),
                    'useTLS' => env('REVERB_SCHEME') === 'https',
                ],
                'client_options' => [
                    'guzzle' => [
                        'verify' => env('REVERB_VERIFY_SSL', true),
                    ],
                ],
            ],
        ],
    ],

];