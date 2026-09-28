<?php

namespace Tests\Feature;

use App\Services\Community;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConsistencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['komuniedad.demo' => true]);
    }

    public function test_other_seniors_records_are_private_and_do_not_block_enrollment(): void
    {
        $entry = ['enrollment_id' => 'other-entry', 'activity_id' => '1', 'senior_id' => 'other', 'status' => 'confirmed', 'enrolled_at' => now()->toIso8601String()];
        $this->withSession(['demo_enrollments' => [$entry]])->getJson('/?mine=1')->assertJsonPath('count', 0);
        $this->postJson('/enrollments/other-entry/withdraw')->assertNotFound();
        $this->get('/history')->assertDontSee('Morning movement');
        $this->postJson('/activities/1/enroll')->assertOk();
        $entry['status'] = 'completed'; $entry['attended'] = true;
        $this->withSession(['demo_enrollments' => [$entry]])->postJson('/history/other-entry/feedback', ['rating' => 5])->assertUnprocessable();
    }

    public function test_logout_clears_session_when_supabase_is_unavailable(): void
    {
        config(['komuniedad.demo' => false, 'komuniedad.url' => 'https://example.test', 'komuniedad.key' => 'test']);
        Http::fake(['*' => Http::response([], 503)]);
        $this->withSession(['access_token' => 'old-token'])->post('/logout')->assertRedirect('/login')->assertSessionMissing('access_token');
    }

    public function test_demo_published_activity_cannot_return_to_draft(): void
    {
        $this->get('/');
        $activity = app(Community::class)->activities()[0];
        $this->withSession(['demo_role' => 'coordinator'])->postJson('/workspace/1/edit', array_replace($activity, ['status' => 'draft']))
            ->assertUnprocessable()->assertJsonValidationErrors('status');
    }

    public function test_coordinator_cannot_retarget_another_coordinators_notice(): void
    {
        $this->get('/');
        $activities = session('demo_activities');
        $activities[1]['coordinator_id'] = 'other-coordinator';
        $notice = ['announcement_id' => 'other-notice', 'activity_id' => '2', 'title' => 'Private management', 'message' => 'Notice', 'archived_at' => null];
        $this->withSession(['demo_role' => 'coordinator', 'demo_activities' => $activities, 'demo_announcements' => [$notice]])
            ->postJson('/announcements', ['announcement_id' => 'other-notice', 'activity_id' => '1', 'title' => 'Changed', 'message' => 'Changed'])->assertNotFound();
    }

    public function test_disabled_demo_account_cannot_access_protected_pages(): void
    {
        $profiles = app(Community::class)->table('profiles');
        $profiles[0]['account_status'] = 'disabled';
        $this->withSession(['demo_profiles' => $profiles])->getJson('/')->assertForbidden();
    }

    public function test_closed_activities_do_not_advertise_available_registration(): void
    {
        $this->get('/');
        $activities = session('demo_activities');
        $activities[0]['status'] = 'cancelled';
        $this->withSession(['demo_activities' => $activities])->get('/?q=Morning')->assertSee('Registration closed')->assertDontSee('6 spots left');
    }

    public function test_full_filter_uses_current_seat_counts(): void
    {
        $this->getJson('/?status=full')->assertJsonPath('count', 1)->assertJsonPath('data.0.activity_id', '3')->assertJsonPath('data.0.status', 'full');
    }
}
