<?php

return [
    'driver' => env('SMS_DRIVER', 'log'),
    'endpoint' => env('SMS_API_ENDPOINT'),
    'token' => env('SMS_API_TOKEN'),
    'auth_header' => env('SMS_AUTH_HEADER', 'Authorization'),
    'auth_prefix' => env('SMS_AUTH_PREFIX', 'Bearer '),
    'phone_field' => env('SMS_PHONE_FIELD', 'to'),
    'message_field' => env('SMS_MESSAGE_FIELD', 'message'),
];
