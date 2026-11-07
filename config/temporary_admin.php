<?php

$useLocalDefaults = env('APP_ENV', 'production') === 'local';

return [
    'email' => env('TEMPORARY_ADMIN_EMAIL') ?: ($useLocalDefaults ? 'admin@fcommerce.demo' : null),
    'password' => env('TEMPORARY_ADMIN_PASSWORD') ?: ($useLocalDefaults ? 'Bikrify-Local-2026!' : null),
];
