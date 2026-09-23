<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InteractiveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['komuniedad.demo' => true]);
    }

    public function test_fetch_search_returns_filtered_data_and_escaped_cards(): void
    {
        $this->getJson('/?q=gardening&category=Learning')->assertOk()
            ->assertJsonPath('count', 1)->assertJsonPath('data.0.title', 'Grow together: urban gardening');
        $this->getJson('/?q=nonexistent')->assertJsonPath('count', 0)->assertJsonPath('data', []);
    }

    public function test_fetch_enrollment_and_withdrawal_persist_and_refresh_counts(): void
    {
        $this->postJson('/activities/1/enroll')->assertOk()->assertJsonStructure(['message', 'redirect']);
        $id = session('demo_enrollments')[0]['enrollment_id'];
        $this->getJson('/?mine=1')->assertJsonPath('count', 1)->assertJsonPath('data.0.confirmed', 19);
        $this->postJson('/activities/1/enroll')->assertUnprocessable()->assertJsonValidationErrors('enrollment');
        $this->postJson('/enrollments/'.$id.'/withdraw')->assertOk();
        $this->getJson('/?mine=1')->assertJsonPath('count', 0);
        $this->getJson('/?q=Morning')->assertJsonPath('data.0.confirmed', 18);
    }

    public function test_staff_json_validation_and_save(): void
    {
        $this->withSession(['demo_role' => 'coordinator'])->postJson('/workspace/create', [])
            ->assertUnprocessable()->assertJsonValidationErrors(['title', 'capacity']);
        $this->postJson('/workspace/create', ['title' => '<script>test</script>', 'description' => 'A workshop', 'venue' => 'Hall', 'category_id' => 'social', 'start_at' => now()->addDays(3)->toDateTimeString(), 'end_at' => now()->addDays(3)->addHour()->toDateTimeString(), 'cutoff_at' => now()->addDays(2)->toDateTimeString(), 'capacity' => 2, 'status' => 'open'])->assertOk();
        $this->withSession(['demo_role' => 'senior'])->getJson('/?q=test')->assertOk()
            ->assertSee('&lt;script&gt;', false)->assertDontSee('<script>test</script>', false);
    }

    public function test_json_guest_and_staff_authorization(): void
    {
        $this->postJson('/administration/categories', ['name' => 'No'])->assertForbidden();
        config(['komuniedad.demo' => false]);
        $this->getJson('/')->assertUnauthorized();
    }

    public function test_temporary_service_failure_preserves_session(): void
    {
        config(['komuniedad.demo' => false, 'komuniedad.url' => 'https://example.test', 'komuniedad.key' => 'test']);
        Http::fake(['*' => Http::response([], 503)]);
        $this->withSession(['access_token' => 'keep-token'])->getJson('/')->assertStatus(503)
            ->assertSessionHas('access_token', 'keep-token');
    }

    public function test_staff_seat_counts_and_repeat_confirmation(): void
    {
        $this->withSession(['demo_role' => 'coordinator']);
        $payload = ['senior_id' => 'demo-senior', 'status' => 'confirmed', 'reason' => 'Authorized walk-in'];
        $this->postJson('/workspace/1/enrollment', $payload)->assertOk();
        $this->postJson('/workspace/1/enrollment', $payload)->assertOk();
        $this->assertSame(19, session('demo_activities')[0]['confirmed']);
        $this->postJson('/workspace/1/enrollment', array_replace($payload, ['status' => 'cancelled']))->assertOk();
        $this->assertSame(18, session('demo_activities')[0]['confirmed']);
    }

    public function test_withdrawal_promotes_waitlisted_demo_participant(): void
    {
        $this->postJson('/activities/1/enroll')->assertOk();
        $entries = session('demo_enrollments');
        $id = $entries[0]['enrollment_id'];
        $entries[] = ['enrollment_id' => 'waiting', 'activity_id' => '1', 'senior_id' => 'demo-other', 'status' => 'waitlisted', 'enrolled_at' => now()->toIso8601String()];
        $this->withSession(['demo_enrollments' => $entries])->postJson('/enrollments/'.$id.'/withdraw')->assertOk();
        $this->assertSame('confirmed', session('demo_enrollments')[1]['status']);
        $this->assertSame(19, session('demo_activities')[0]['confirmed']);
    }

    public function test_publishable_key_is_not_sent_as_a_user_bearer_token(): void
    {
        config(['komuniedad.url' => 'https://example.test', 'komuniedad.key' => 'sb_publishable_test']);
        Http::fake(['*' => Http::response(['access_token' => 'user-token'])]);
        $this->post('/login', ['email' => 'test@example.com', 'password' => 'password'])->assertRedirect('/');
        Http::assertSent(fn ($r) => $r->hasHeader('apikey', 'sb_publishable_test') && ! $r->hasHeader('Authorization'));
    }
}
