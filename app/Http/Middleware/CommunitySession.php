<?php

namespace App\Http\Middleware;

use App\Services\Community;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CommunitySession
{
    public function handle(Request $r, Closure $next)
    {
        $s = app(Community::class);
        if ($s->demo()) {
            $profile = collect($s->table('profiles'))->firstWhere('user_id', 'demo-'.$s->role());
            abort_unless($profile && $profile['account_status'] === 'active' && $profile['role'] === $s->role(), 403);
            return $next($r);
        }
        if (! $s->accessToken()) {
            abort_if($r->expectsJson(), 401, 'Please sign in to continue.');
            return redirect('/login');
        }
        $cachedProfile = session('profile');
        $verifiedAt = (int) session('profile_verified_at', 0);
        $tokenHash = hash('sha256', (string) $s->accessToken());
        $cachedTokenHash = session('profile_token_hash');

        // Never trust a cached profile unless it was resolved for this exact
        // access token. This prevents identity details leaking across logins.
        if (is_array($cachedProfile)
            && ($cachedProfile['account_status'] ?? null) === 'active'
            && is_string($cachedTokenHash)
            && hash_equals($cachedTokenHash, $tokenHash)
            && time() - $verifiedAt < 60) {
            $r->attributes->set('verified_profile', $cachedProfile);

            return $next($r);
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
            Cookie::queue(Cookie::forget(Community::AUTH_COOKIE));
            abort_if($r->expectsJson(), 401, 'Your session has expired. Please sign in again.');

            return redirect('/login')->withErrors(['account' => 'Please sign in again.']);
        }
        abort_unless(isset($p[0]) && $p[0]['account_status'] === 'active', 403);
        session([
            'profile' => $p[0],
            'profile_verified_at' => time(),
            'profile_token_hash' => $tokenHash,
        ]);
        $r->attributes->set('verified_profile', $p[0]);

        return $next($r);
    }
}
