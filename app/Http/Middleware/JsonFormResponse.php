<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class JsonFormResponse
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if ($request->expectsJson() && $request->isMethod('post') && $response instanceof RedirectResponse) {
            return response()->json([
                'message' => $request->session()->pull('status', 'Changes saved.'),
                'redirect' => $response->getTargetUrl(),
            ]);
        }

        return $response;
    }
}
