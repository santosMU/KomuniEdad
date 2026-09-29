<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Vercel serverless entry point
|--------------------------------------------------------------------------
|
| Vercel's deployed application filesystem is read-only. Laravel runtime
| files therefore use /tmp. When PostgreSQL is configured, sessions and
| cache/rate-limit state use the shared database so they survive across
| independent serverless invocations.
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

$dbUrl = getenv('DB_URL') ?: null;
$dbConfigured = (bool) $dbUrl || (
    (getenv('DB_CONNECTION') ?: '') !== ''
    && (getenv('DB_HOST') ?: '') !== ''
    && (getenv('DB_DATABASE') ?: '') !== ''
    && (getenv('DB_USERNAME') ?: '') !== ''
    && (getenv('DB_PASSWORD') ?: '') !== ''
);

// On Vercel, prefer shared PostgreSQL state whenever database credentials
// are present. This intentionally removes Redis as a deployment requirement.
// Cookie sessions remain only as a fallback when no shared database exists.
$sessionDriver = $dbConfigured
    ? 'database'
    : (getenv('SESSION_DRIVER') ?: 'cookie');

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

if ($dbConfigured) {
    // Use Supabase/PostgreSQL for shared session and limiter state.
    $serverless['CACHE_STORE'] = 'database';
    $serverless['CACHE_LIMITER'] = 'database';

    if ((getenv('DB_CONNECTION') ?: '') === 'pgsql' && ! getenv('DB_SSLMODE')) {
        $serverless['DB_SSLMODE'] = 'require';
    }
} else {
    // This fallback keeps public pages bootable without a database, but it is
    // not suitable for production authentication or cross-instance throttling.
    if (! getenv('CACHE_STORE')) {
        $serverless['CACHE_STORE'] = 'array';
    }
}

foreach ($serverless as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

require __DIR__.'/../public/index.php';
