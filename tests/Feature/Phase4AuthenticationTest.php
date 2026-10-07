<?php

namespace Tests\Feature;

use App\Services\Community;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Phase4TestCase;

class Phase4AuthenticationTest extends Phase4TestCase
{
    private function authReply(array $body, int $status = 200): void
    {
        config(['komuniedad.demo' => false, 'komuniedad.url' => 'https://supabase.example.test', 'komuniedad.key' => 'synthetic-key']);
        Http::fake(['*' => Http::response($body, $status)]);
    }

    public function test_AUTH_01_valid_login(): void
    {
        $this->authReply(['access_token' => 'synthetic-token', 'expires_in' => 3600]);
        $this->post('/login', $this->registration())->assertRedirect('/')->assertCookie(Community::AUTH_COOKIE, 'synthetic-token');
    }

    public static function invalidLogin(): array
    {
        return ['AUTH-02' => ['email', 'senior@example.test', 400], 'AUTH-03' => ['email', 'absent@example.test', 400], 'AUTH-04' => ['email', '', 422], 'AUTH-05' => ['password', '', 422], 'AUTH-06' => ['email', 'abc', 422]];
    }

    #[DataProvider('invalidLogin')]
    public function test_invalid_login(string $field, string $value, int $remote): void
    {
        $this->authReply(['message' => 'Invalid credentials'], $remote);
        $this->postJson('/login', array_replace($this->registration(), [$field => $value]))->assertUnprocessable()->assertCookieMissing(Community::AUTH_COOKIE);
    }

    public function test_AUTH_07_logout(): void
    {
        $this->live();
        $this->post('/logout')->assertRedirect('/login')->assertCookieExpired(Community::AUTH_COOKIE)->assertSessionMissing('access_token');
    }
    public function test_AUTH_08_protected_after_logout(): void
    {
        $this->live(); $this->post('/logout'); $this->get('/profile')->assertRedirect('/login');
    }
    public function test_AUTH_09_missing_token_json(): void { config(['komuniedad.demo' => false]); $this->getJson('/profile')->assertUnauthorized(); }

    public static function rejectedTokens(): array { return ['AUTH-10' => ['Invalid JWT'], 'AUTH-11' => ['JWT expired']]; }
    #[DataProvider('rejectedTokens')]
    public function test_invalid_or_expired_token(string $message): void
    {
        $this->authReply(['message' => $message], 401);
        $this->withSession(['access_token' => 'synthetic-token'])->getJson('/profile')->assertUnauthorized()->assertSessionMissing('access_token')->assertCookieExpired(Community::AUTH_COOKIE);
    }
    public function test_AUTH_12_disabled_account(): void { $this->live('senior', 'disabled'); $this->getJson('/profile')->assertForbidden(); }
    public function test_AUTH_13_login_rotates_session(): void
    {
        $this->authReply(['access_token' => 'synthetic-token', 'expires_in' => 3600]);
        $this->withSession(['sentinel' => true]); $id = session()->getId();
        $this->post('/login', $this->registration())->assertRedirect('/'); $this->assertNotSame($id, session()->getId());
    }
    public function test_login_clears_cached_identity_from_previous_account(): void
    {
        $this->authReply(['access_token' => 'admin-token', 'expires_in' => 3600]);

        $this->withSession([
            'profile' => [
                'user_id' => 'previous-senior',
                'full_name' => 'Previous Senior',
                'role' => 'senior',
                'account_status' => 'active',
            ],
            'profile_verified_at' => time(),
            'profile_token_hash' => hash('sha256', 'previous-token'),
        ]);

        $this->post('/login', $this->registration())
            ->assertRedirect('/')
            ->assertSessionMissing('profile')
            ->assertSessionMissing('profile_verified_at')
            ->assertSessionMissing('profile_token_hash');
    }

    public function test_AUTH_14_logout_rotates_csrf(): void
    {
        $this->live(); $this->withSession(['_token' => 'synthetic-old-csrf', 'sentinel' => true]);
        $this->post('/logout')->assertSessionMissing('sentinel'); $this->assertNotSame('synthetic-old-csrf', session()->token());
    }
    public static function throttles(): array { return ['AUTH-15' => ['/login', 6], 'AUTH-16' => ['/register', 5]]; }
    #[DataProvider('throttles')]
    public function test_throttle(string $url, int $limit): void
    {
        for ($i = 0; $i < $limit; $i++) $this->postJson($url, [])->assertUnprocessable();
        $this->postJson($url, [])->assertStatus(429);
    }
    public function test_AUTH_17_password_minimum(): void { $this->postJson('/register', array_replace($this->registration(), ['password' => 'short', 'password_confirmation' => 'short']))->assertJsonValidationErrors('password'); }
    public function test_AUTH_18_password_confirmation(): void { $this->postJson('/register', array_replace($this->registration(), ['password_confirmation' => 'different']))->assertJsonValidationErrors('password'); }
    public function test_AUTH_19_registration_metadata(): void
    {
        $this->authReply(['user' => ['id' => 'synthetic-id']]);
        $this->post('/register', $this->registration() + ['role' => 'admin', 'account_status' => 'active', 'data' => ['role' => 'admin']])->assertRedirect('/login');
        Http::assertSent(fn ($r) => $r['data'] === ['full_name' => 'Synthetic Senior'] && !isset($r['role']));
    }
    public function test_AUTH_20_error_is_safe(): void
    {
        $this->authReply(['message' => 'SQLSTATE secret-marker /private/server.php', 'details' => 'private stack'], 400);
        $this->postJson('/login', $this->registration())->assertUnprocessable()->assertDontSee('secret-marker')->assertDontSee('SQLSTATE')->assertDontSee('/private');
    }
    public static function cookies(): array { return ['AUTH-21' => ['httpOnly'], 'AUTH-22' => ['sameSite'], 'AUTH-23' => ['lifetime'], 'AUTH-24' => ['secure']]; }
    #[DataProvider('cookies')]
    public function test_cookie_properties(string $property): void
    {
        config(['session.secure' => $property === 'secure', 'session.same_site' => 'lax', 'session.lifetime' => 120]);
        $this->authReply(['access_token' => 'synthetic-token', 'expires_in' => 3600]);
        $before = time();
        $response = $this->post('/login', $this->registration())->assertRedirect('/');
        $cookie = $response->getCookie(Community::AUTH_COOKIE);
        match ($property) {
            'httpOnly' => $this->assertTrue($cookie->isHttpOnly()),
            'sameSite' => $this->assertSame('lax', $cookie->getSameSite()),
            'secure' => $this->assertTrue($cookie->isSecure()),
            'lifetime' => $this->assertLessThanOrEqual($before + 3600, $cookie->getExpiresTime()),
        };
    }
}
