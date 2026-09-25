<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default credentials
    |--------------------------------------------------------------------------
    |
    | Used when no credentials are supplied via Cap::withCredentials(). Multi
    | tenant applications should pass per-tenant credentials explicitly.
    |
    */

    'subscriber_id' => env('CAP_SUBSCRIBER_ID'),

    'password' => env('CAP_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Endpoints
    |--------------------------------------------------------------------------
    */

    'base_url' => env('CAP_BASE_URL', 'https://soap.cap.co.uk'),

    'image_url' => env('CAP_IMAGE_URL', 'https://soap.cap.co.uk/images/VehicleImage.aspx'),

    /*
    |--------------------------------------------------------------------------
    | Default database
    |--------------------------------------------------------------------------
    |
    | CAR or LCV. Used when a call does not specify a database explicitly.
    |
    */

    'default_database' => env('CAP_DEFAULT_DATABASE', 'CAR'),

    /*
    |--------------------------------------------------------------------------
    | Transport
    |--------------------------------------------------------------------------
    |
    | Retries only apply to connection failures and 502/503/504 responses.
    | SOAP faults (HTTP 500) are never retried.
    |
    */

    'timeout' => (int) env('CAP_TIMEOUT', 15),

    'connect_timeout' => (int) env('CAP_CONNECT_TIMEOUT', 5),

    'retry' => [
        'times' => (int) env('CAP_RETRY_TIMES', 2),
        'sleep_ms' => (int) env('CAP_RETRY_SLEEP_MS', 250),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | When enabled, each call logs its service, operation, status and duration.
    | Request bodies (which contain credentials) are never logged.
    |
    */

    'logging' => [
        'enabled' => (bool) env('CAP_LOG', false),
        'channel' => env('CAP_LOG_CHANNEL'),
    ],

];
