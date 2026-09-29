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

$redisUrl = getenv('REDIS_URL') ?: null;
$sessionDriver = getenv('SESSION_DRIVER') ?: ($redisUrl ? 'redis' : 'cookie');

$serverless = [
    'LARAVEL_STORAGE_PATH' => $storagePath,
    'APP_CONFIG_CACHE' => $runtimePath.'/bootstrap/cache/config.php',
    'APP_EVENTS_CACHE' => $runtimePath.'/bootstrap/cache/events.php',
    'APP_PACKAGES_CACHE' => $runtimePath.'/bootstrap/cache/packages.php',
    'APP_ROUTES_CACHE' => $runtimePath.'/bootstrap/cache/routes.php',
    'APP_SERVICES_CACHE' => $runtimePath.'/bootstrap/cache/services.php',
    'VIEW_COMPILED_PATH' => $storagePath.'/framework/views',
    'SESSION_DRIVER' => $sessionDriver,
    'SESSION_ENCRYPT' => 'true',
    'SESSION_SECURE_COOKIE' => 'true',
    'SESSION_HTTP_ONLY' => 'true',
    'SESSION_SAME_SITE' => 'lax',
    'SESSION_PATH' => '/',
    // Vercel Preview and Production aliases may use different hostnames.
    // A host-only session cookie prevents CSRF/session loss from a stale
    // SESSION_DOMAIN that points at another deployment or production host.
    'SESSION_DOMAIN' => '',
    'LOG_CHANNEL' => 'stderr',
    'LOG_STACK' => 'stderr',
    'APP_DEBUG' => 'false',
];

// Prefer the shared Redis store whenever Vercel has provisioned REDIS_URL.
// This keeps CSRF/session state and rate limits consistent across serverless
// invocations. Cookie sessions remain a compatibility fallback only.
if ($sessionDriver === 'redis') {
    $serverless['SESSION_CONNECTION'] = getenv('SESSION_CONNECTION') ?: 'default';
}

if (! getenv('CACHE_STORE')) {
    $serverless['CACHE_STORE'] = $redisUrl ? 'redis' : 'array';
}
if (! getenv('CACHE_LIMITER') && $redisUrl) {
    $serverless['CACHE_LIMITER'] = 'redis';
}

foreach ($serverless as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

require __DIR__.'/../public/index.php';
