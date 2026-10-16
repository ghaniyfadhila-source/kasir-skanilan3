<?php

/*
 * The kasir POS polls GET /api/transactions/{id}/status from the browser. That
 * request only carries a session, so Sanctum has to treat it as a stateful
 * "frontend" call - which it decides by matching the referer/origin against
 * this list. `localhost` alone does not cover `localhost:8000`, so the host and
 * port of APP_URL are appended as well.
 */
$appUrl = env('APP_URL');
$appHost = $appUrl ? parse_url($appUrl, PHP_URL_HOST) : null;
$appPort = $appUrl ? parse_url($appUrl, PHP_URL_PORT) : null;
$appTarget = $appHost ? ($appPort ? $appHost.':'.$appPort : $appHost) : '';

return [

    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:8000,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        $appTarget ? ','.$appTarget : ''
    ))),

    'expiration' => null,

    'token_prefix' => '',

    'personal_access_token' => [
        'expiration' => 365 * 24 * 60, // 1 year for device tokens
    ],

];
