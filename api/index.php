<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Vercel serverless entry point
|--------------------------------------------------------------------------
|
| Vercel's deployed application filesystem is read-only. Laravel runtime
| files therefore use /tmp. Shared session/cache state is stored in the
| Supabase PostgreSQL database already connected to the Vercel project.
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

// Accept either Laravel-style names or the variable names created by the
// Vercel Supabase integration. This avoids requiring a second database setup.
$dbUrl = getenv('DB_URL')
    ?: getenv('POSTGRES_URL')
    ?: getenv('POSTGRES_PRISMA_URL')
    ?: null;

$supabaseUrl = getenv('SUPABASE_URL')
    ?: getenv('NEXT_PUBLIC_SUPABASE_URL')
    ?: null;

$supabaseAnonKey = getenv('SUPABASE_ANON_KEY')
    ?: getenv('NEXT_PUBLIC_SUPABASE_ANON_KEY')
    ?: getenv('SUPABASE_PUBLISHABLE_KEY')
    ?: null;

$dbConfigured = (bool) $dbUrl || (
    (getenv('DB_CONNECTION') ?: '') !== ''
    && (getenv('DB_HOST') ?: '') !== ''
    && (getenv('DB_DATABASE') ?: '') !== ''
    && (getenv('DB_USERNAME') ?: '') !== ''
    && (getenv('DB_PASSWORD') ?: '') !== ''
);

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
    'APP_ENV' => 'production',
    'APP_DEBUG' => 'false',
    'KOMUNIEDAD_DEMO' => 'false',
    'SESSION_DRIVER' => $sessionDriver,
    'SESSION_TABLE' => getenv('SESSION_TABLE') ?: 'sessions',
    'SESSION_ENCRYPT' => 'true',
    'SESSION_SECURE_COOKIE' => 'true',
    'SESSION_HTTP_ONLY' => 'true',
    'SESSION_SAME_SITE' => 'lax',
    'SESSION_PATH' => '/',
    // Preview and Production aliases use different hosts. A host-only cookie
    // avoids stale-domain CSRF/session failures.
    'SESSION_DOMAIN' => '',
    'LOG_CHANNEL' => 'stderr',
    'LOG_STACK' => 'stderr',
];

if (! getenv('APP_URL')) {
    $productionHost = getenv('VERCEL_PROJECT_PRODUCTION_URL') ?: 'komuni-edad.vercel.app';
    $serverless['APP_URL'] = str_starts_with($productionHost, 'http')
        ? $productionHost
        : 'https://'.$productionHost;
}

if ($dbConfigured) {
    $serverless['DB_CONNECTION'] = getenv('DB_CONNECTION') ?: 'pgsql';

    if ($dbUrl) {
        $serverless['DB_URL'] = $dbUrl;
    }

    if (($serverless['DB_CONNECTION'] ?? '') === 'pgsql' && ! getenv('DB_SSLMODE')) {
        $serverless['DB_SSLMODE'] = 'require';
    }

    $serverless['CACHE_STORE'] = 'database';
    $serverless['CACHE_LIMITER'] = 'database';
    $serverless['DB_CACHE_TABLE'] = getenv('DB_CACHE_TABLE') ?: 'cache';
    $serverless['DB_CACHE_LOCK_TABLE'] = getenv('DB_CACHE_LOCK_TABLE') ?: 'cache_locks';
} elseif (! getenv('CACHE_STORE')) {
    // Public pages can still boot without a DB, but authenticated production
    // traffic requires shared database-backed session state.
    $serverless['CACHE_STORE'] = 'array';
}

if ($supabaseUrl && ! getenv('SUPABASE_URL')) {
    $serverless['SUPABASE_URL'] = $supabaseUrl;
}

if ($supabaseAnonKey && ! getenv('SUPABASE_ANON_KEY')) {
    $serverless['SUPABASE_ANON_KEY'] = $supabaseAnonKey;
}

foreach ($serverless as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

require __DIR__.'/../public/index.php';
