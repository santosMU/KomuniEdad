<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Vercel serverless entry point
|--------------------------------------------------------------------------
|
| Vercel functions have a read-only project filesystem. Laravel's runtime
| cache, compiled views and sessions therefore use /tmp or cookies.
|
*/

$runtimePath = '/tmp/komuniedad';

foreach ([
    $runtimePath.'/bootstrap/cache',
    $runtimePath.'/framework/cache/data',
    $runtimePath.'/framework/sessions',
    $runtimePath.'/framework/views',
    $runtimePath.'/logs',
] as $directory) {
    if (! is_dir($directory)) {
        @mkdir($directory, 0755, true);
    }
}

$defaults = [
    'APP_CONFIG_CACHE' => $runtimePath.'/bootstrap/cache/config.php',
    'APP_EVENTS_CACHE' => $runtimePath.'/bootstrap/cache/events.php',
    'APP_PACKAGES_CACHE' => $runtimePath.'/bootstrap/cache/packages.php',
    'APP_ROUTES_CACHE' => $runtimePath.'/bootstrap/cache/routes.php',
    'APP_SERVICES_CACHE' => $runtimePath.'/bootstrap/cache/services.php',
    'VIEW_COMPILED_PATH' => $runtimePath.'/framework/views',
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'cookie',
    'SESSION_ENCRYPT' => 'true',
    'SESSION_SECURE_COOKIE' => 'true',
    'LOG_CHANNEL' => 'stderr',
];

foreach ($defaults as $key => $value) {
    if (getenv($key) === false || getenv($key) === '') {
        putenv($key.'='.$value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

require __DIR__.'/../public/index.php';
