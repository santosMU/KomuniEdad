<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Vercel serverless entry point
|--------------------------------------------------------------------------
|
| Vercel's deployed application filesystem is read-only. Laravel runtime
| files therefore use /tmp, while sessions use encrypted cookies and logs
| are sent to stderr.
|
*/

$runtimePath = '/tmp/komuniedad';
$storagePath = $runtimePath.'/storage';

foreach ([
    $runtimePath.'/bootstrap/cache',
    $storagePath.'/framework/cache/data',
    $storagePath.'/framework/sessions',
    $storagePath.'/framework/views',
    $storagePath.'/logs',
] as $directory) {
    if (! is_dir($directory)) {
        @mkdir($directory, 0755, true);
    }
}

$serverless = [
    'LARAVEL_STORAGE_PATH' => $storagePath,
    'APP_CONFIG_CACHE' => $runtimePath.'/bootstrap/cache/config.php',
    'APP_EVENTS_CACHE' => $runtimePath.'/bootstrap/cache/events.php',
    'APP_PACKAGES_CACHE' => $runtimePath.'/bootstrap/cache/packages.php',
    'APP_ROUTES_CACHE' => $runtimePath.'/bootstrap/cache/routes.php',
    'APP_SERVICES_CACHE' => $runtimePath.'/bootstrap/cache/services.php',
    'VIEW_COMPILED_PATH' => $storagePath.'/framework/views',
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'cookie',
    'SESSION_ENCRYPT' => 'true',
    'SESSION_SECURE_COOKIE' => 'true',
    'LOG_CHANNEL' => 'stderr',
    'LOG_STACK' => 'stderr',
    'APP_DEBUG' => 'false',
];

foreach ($serverless as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

require __DIR__.'/../public/index.php';
