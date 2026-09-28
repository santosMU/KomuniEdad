<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CommunityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['komuniedad.demo' => true]);
    }

    public function test_search_and_category_filter(): void
    {
        $this->get('/?category=Learning&q=gardening')->assertOk()->assertSee('Grow together')->assertDontSee('Kwentuhan &amp; coffee', false);
    }

    public function test_enrollment_duplicate_and_withdrawal(): void
    {
        $this->post('/activities/1/enroll')->assertRedirect('/?mine=1');
        $this->assertSame('confirmed', session('demo_enrollments')[0]['status']);
        $id = session('demo_enrollments')[0]['enrollment_id'];
        $this->post('/activities/1/enroll')->assertSessionHasErrors('enrollment');
        $this->assertCount(1, session('demo_enrollments'));
        $this->post('/enrollments/'.$id.'/withdraw')->assertRedirect();
        $this->assertSame('cancelled', session('demo_enrollments')[0]['status']);
    }

    public function test_full_activity_uses_waitlist(): void
    {
        $this->post('/activities/3/enroll')->assertRedirect();
        $this->assertSame('waitlisted', session('demo_enrollments')[0]['status']);
    }

    public function test_invalid_activity_and_enrollment_are_not_found(): void
    {
        $this->get('/activities/unknown')->assertNotFound();
        $this->post('/activities/unknown/enroll')->assertNotFound();
        $this->post('/enrollments/unknown/withdraw')->assertNotFound();
    }

    public function test_live_requires_authentication(): void
    {
        config(['komuniedad.demo' => false]);
        $this->get('/')->assertRedirect('/login');
    }

    public function test_invalid_live_token_is_rejected(): void
    {
        config(['komuniedad.demo' => false, 'komuniedad.url' => 'https://example.test', 'komuniedad.key' => 'test']);
        Http::fake(['*' => Http::response([], 401)]);
        $this->withSession(['access_token' => 'invalid'])->get('/')->assertRedirect('/login')->assertSessionMissing('access_token');
    }

    public function test_live_enrollment_calls_authenticated_rpc(): void
    {
        config(['komuniedad.demo' => false, 'komuniedad.url' => 'https://example.test', 'komuniedad.key' => 'test']);
        Http::fake(['*/auth/v1/user' => Http::response(['id' => 'senior-1']), '*/rest/v1/profiles*' => Http::response([['user_id' => 'senior-1', 'role' => 'senior', 'account_status' => 'active']]), '*/rest/v1/rpc/enroll_in_activity' => Http::response(['status' => 'confirmed'])]);
        $this->withSession(['access_token' => 'valid-token'])->post('/activities/activity-1/enroll')->assertRedirect('/?mine=1');
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/rpc/enroll_in_activity') && $r->hasHeader('Authorization', 'Bearer valid-token') && $r['target'] === 'activity-1');
    }
}
