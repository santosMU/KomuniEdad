<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken as Middleware;

class ValidateCsrfToken extends Middleware
{
    /**
     * Blade forms submit the session-backed _token field directly, so the
     * additional XSRF-TOKEN response cookie is unnecessary here. Keeping only
     * the Laravel session cookie also avoids multi-Set-Cookie folding problems
     * on the Vercel PHP/serverless path.
     */
    protected $addHttpCookie = false;
}
