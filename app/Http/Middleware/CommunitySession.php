<?php

namespace App\Http\Middleware;

use App\Services\Community;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CommunitySession
{
    public function handle(Request $r, Closure $next)
    {
        $s = app(Community::class);
        if ($s->demo()) {
            return $next($r);
        }
        if (! session('access_token')) {
            abort_if($r->expectsJson(), 401, 'Please sign in to continue.');
            return redirect('/login');
        }
        try {
            $u = $s->api('GET', '/auth/v1/user');
            $p = $s->api('GET', '/rest/v1/profiles', ['select' => '*', 'user_id' => 'eq.'.$u['id']]);
        } catch (HttpException $e) {
            if ($e->getStatusCode() !== 401) {
                throw $e;
            }
            $r->session()->invalidate();
            $r->session()->regenerateToken();
            abort_if($r->expectsJson(), 401, 'Your session has expired. Please sign in again.');
            return redirect('/login')->withErrors(['account' => 'Please sign in again.']);
        }
        abort_unless(isset($p[0]) && $p[0]['account_status'] === 'active', 403);
        session(['profile' => $p[0]]);
        $r->attributes->set('verified_profile', $p[0]);

        return $next($r);
    }
}
