<?php

namespace Tests\Feature;

use App\Services\Community;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Phase4TestCase;

// Exercise the actual CSRF middleware while disabling only its test-run bypass.
class EnforcedPhase4Csrf extends ValidateCsrfToken
{
    protected function runningUnitTests() { return false; }
}

class Phase4IntegrityTest extends Phase4TestCase
{
    public function test_CSRF_01_missing_and_invalid_tokens(): void
    {
        $this->app->bind(ValidateCsrfToken::class, EnforcedPhase4Csrf::class);
        foreach (['/profile', '/login', '/register', '/logout', '/activities/1/enroll', '/announcements', '/administration/categories'] as $url) {
            $this->postJson($url, [])->assertStatus(419);
            $this->postJson($url, ['_token' => 'invalid'])->assertStatus(419);
        }
    }
    public function test_CSRF_02_correct_fetch_header(): void
    {
        $this->app->bind(ValidateCsrfToken::class, EnforcedPhase4Csrf::class);
        $this->withSession(['_token' => 'synthetic-csrf']);
        $this->postJson('/profile', ['full_name' => 'Valid update'], ['X-CSRF-TOKEN' => 'synthetic-csrf'])->assertOk();
    }
    public static function errors(): array { return ['ERROR-01' => [500], 'ERROR-02' => [503], 'ERROR-03' => [400]]; }
    #[DataProvider('errors')]
    public function test_safe_upstream_error(int $status): void
    {
        config(['komuniedad.demo' => false, 'komuniedad.url' => 'https://supabase.example.test', 'komuniedad.key' => 'synthetic-key']);
        Http::fake(['*' => Http::response(['message' => 'SQLSTATE private-marker /private/server.php'], $status)]);
        $this->postJson('/login', $this->registration())->assertStatus($status >= 500 ? 503 : 422)->assertDontSee('private-marker')->assertDontSee('SQLSTATE')->assertDontSee('/private');
    }
    public function test_ERROR_04_connection_failure(): void
    {
        config(['komuniedad.demo' => false, 'komuniedad.url' => 'https://supabase.example.test', 'komuniedad.key' => 'synthetic-key']);
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('private-marker'));
        $this->withSession(['access_token' => 'synthetic-token'])->getJson('/profile')->assertStatus(503)->assertDontSee('private-marker')->assertSessionHas('access_token');
    }
    public function test_HYP_02_profile_forms_are_not_nested(): void
    {
        $this->live(); $html = $this->get('/profile')->assertOk()->getContent();
        preg_match_all('/<\/?form\b[^>]*>/i', $html, $tags);
        $depth = 0;
        foreach ($tags[0] as $tag) {
            $depth += str_starts_with($tag, '</') ? -1 : 1;
            $this->assertContains($depth, [0, 1], 'Nested or unmatched form: '.$tag);
        }
        $this->assertSame(0, $depth);
    }
    public function test_HYP_04_security_headers(): void
    {
        foreach (['/login', '/register', '/health'] as $path) {
            $response = $this->get($path)->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'DENY')->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
            $this->assertStringContainsString("script-src 'self';", $response->headers->get('Content-Security-Policy'));
            $this->assertStringContainsString("default-src 'self';", $response->headers->get('Content-Security-Policy'));
            $this->assertFalse($response->headers->has('X-Powered-By'));
        }
    }
    public function test_HYP_04_auth_controls_work_with_external_scripts_only(): void
    {
        foreach (['/login', '/register'] as $path) {
            $response = $this->get($path)->assertOk()->assertSee('data-show-password', false)->assertSee('src="/images/logo.png"', false);
            $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*\bsrc=)|\bonclick=/i', $response->getContent());
        }
    }
    public function test_AUTH_25_configured_limiter_survives_application_restarts(): void
    {
        $path = storage_path('framework/cache/phase4-'.bin2hex(random_bytes(8)));
        $files = new \Illuminate\Filesystem\Filesystem;
        try {
            for ($attempt = 1; $attempt <= 7; $attempt++) {
                $this->refreshApplication();
                config(['komuniedad.demo' => true, 'cache.default' => 'array', 'cache.limiter' => 'file', 'cache.stores.file.path' => $path]);
                $this->postJson('/login', [])->assertStatus($attempt <= 6 ? 422 : 429);
            }
        } finally {
            // Remove only the unique cache directory created by this test.
            $files->deleteDirectory($path);
        }
    }
    public function test_ERROR_05_redirect_cannot_be_protocol_relative(): void
    {
        $this->postJson('/profile', ['full_name' => 'Synthetic Senior'], ['Referer' => 'https://example.test//external.example/path'])
            ->assertOk()->assertJsonPath('redirect', '/external.example/path');
    }
    public function test_ERROR_06_malformed_id_never_reaches_rpc(): void
    {
        $this->live();
        $this->postJson('/activities/malformed/enroll')->assertUnprocessable()->assertJsonValidationErrors('id');
        Http::assertNotSent(fn ($r) => str_contains($r->url(), '/rpc/'));
    }
    public function test_ERROR_07_unauthorized_and_validation_errors_are_safe(): void
    {
        $this->getJson('/administration')->assertForbidden()->assertDontSee('exception')->assertDontSee('trace');
        $this->postJson('/profile', [])->assertUnprocessable()->assertJsonValidationErrors('full_name')->assertDontSee('exception')->assertDontSee('trace');
    }
}
