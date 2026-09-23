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
                'redirect' => $this->relativeTarget($response->getTargetUrl()),
            ])->header('Cache-Control', 'private, no-store');
        }

        return $response;
    }

    private function relativeTarget(string $target): string
    {
        $parts = parse_url($target);
        if ($parts === false) {
            return '/';
        }

        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return ($path !== '' ? $path : '/').$query;
    }
}
