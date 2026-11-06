<?php

use App\Http\Middleware\VerifyCourierWebhookSecret;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Courier Driver
    |--------------------------------------------------------------------------
    |
    | The default courier driver to use when no driver is explicitly specified.
    | Supported: "pathao", "steadfast", "redx", "ecourier", "paperfly"
    |
    */

    'default' => env('COURIER_DEFAULT', 'pathao'),

    /*
    |--------------------------------------------------------------------------
    | Courier Configurations
    |--------------------------------------------------------------------------
    |
    | Each courier can be independently enabled/disabled and configured.
    | Set 'enabled' to false to completely disable a courier — attempting
    | to use a disabled courier will throw CourierDisabledException.
    |
    */

    'couriers' => [

        'pathao' => [
            'enabled' => env('COURIER_PATHAO_ENABLED', false),
            'sandbox' => env('COURIER_PATHAO_SANDBOX', true),
            'client_id' => env('COURIER_PATHAO_CLIENT_ID'),
            'client_secret' => env('COURIER_PATHAO_CLIENT_SECRET'),
            'username' => env('COURIER_PATHAO_USERNAME'),
            'password' => env('COURIER_PATHAO_PASSWORD'),
            'default_store_id' => env('COURIER_PATHAO_STORE_ID'),
            'base_url' => [
                'sandbox' => 'https://hermes-api.p-stageenv.xyz',
                'production' => 'https://api-hermes.pathao.com',
            ],
        ],

        'steadfast' => [
            'enabled' => env('COURIER_STEADFAST_ENABLED', false),
            'api_key' => env('COURIER_STEADFAST_API_KEY'),
            'secret_key' => env('COURIER_STEADFAST_SECRET_KEY'),
            'base_url' => 'https://portal.steadfast.com.bd/api/v1',
        ],

        'redx' => [
            'enabled' => env('COURIER_REDX_ENABLED', false),
            'sandbox' => env('COURIER_REDX_SANDBOX', true),
            'access_token' => env('COURIER_REDX_ACCESS_TOKEN'),
            'base_url' => [
                'sandbox' => 'https://sandbox.redx.com.bd/v1.0.0-beta',
                'production' => 'https://openapi.redx.com.bd/v1.0.0-beta',
            ],
        ],

        'ecourier' => [
            'enabled' => env('COURIER_ECOURIER_ENABLED', false),
            'sandbox' => env('COURIER_ECOURIER_SANDBOX', true),
            'api_key' => env('COURIER_ECOURIER_API_KEY'),
            'api_secret' => env('COURIER_ECOURIER_API_SECRET'),
            'user_id' => env('COURIER_ECOURIER_USER_ID'),
            'base_url' => [
                'sandbox' => 'https://staging.ecourier.com.bd/api',
                'production' => 'https://backoffice.ecourier.com.bd/api',
            ],
        ],

        'paperfly' => [
            'enabled' => env('COURIER_PAPERFLY_ENABLED', false),
            'username' => env('COURIER_PAPERFLY_USERNAME'),
            'password' => env('COURIER_PAPERFLY_PASSWORD'),
            'api_key' => env('COURIER_PAPERFLY_API_KEY'),
            'base_url' => env('COURIER_PAPERFLY_BASE_URL', 'https://go-app.paperfly.com.bd/merchant/api'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the webhook endpoint that receives status updates from
    | courier services. Each courier can have its own webhook secret
    | for signature verification.
    |
    */

    'webhook' => [
        'path' => 'webhooks/courier',
        'middleware' => [VerifyCourierWebhookSecret::class],
        'secrets' => [
            'pathao' => env('COURIER_PATHAO_WEBHOOK_SECRET'),
            'steadfast' => env('COURIER_STEADFAST_WEBHOOK_SECRET'),
            'redx' => env('COURIER_REDX_WEBHOOK_SECRET'),
            'ecourier' => env('COURIER_ECOURIER_WEBHOOK_SECRET'),
            'paperfly' => env('COURIER_PAPERFLY_WEBHOOK_SECRET'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Area/city/zone lookups are cached to reduce API calls. Set 'enabled'
    | to false to disable caching. TTL is in seconds.
    |
    */

    'cache' => [
        'enabled' => env('COURIER_CACHE_ENABLED', true),
        'ttl' => env('COURIER_CACHE_TTL', 3600),
        'prefix' => 'courierhub',
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Configuration
    |--------------------------------------------------------------------------
    |
    | Default timeout, retry count, and retry delay for all API requests.
    | Individual drivers may override these if needed.
    |
    */

    'http' => [
        'timeout' => 30,
        'retry' => env('COURIER_HTTP_RETRY', 0),
        'retry_delay' => 100, // milliseconds
    ],

];
