<?php

namespace Tests\Feature;

use App\Services\Community;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PortalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['komuniedad.demo' => true]);
    }

    public function test_senior_cannot_access_staff_or_admin_pages(): void
    {
        foreach (['/workspace', '/workspace/create', '/reports', '/administration'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->post('/administration/categories', ['name' => 'Test', 'is_active' => 1])->assertForbidden();
    }

    public function test_coordinator_pages_render_and_admin_is_denied(): void
    {
        $this->withSession(['demo_role' => 'coordinator']);
        foreach (['/workspace', '/workspace/create', '/workspace/1/edit', '/workspace/1/participants', '/announcements', '/reports', '/profile'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/administration')->assertForbidden();
        $this->post('/activities/1/enroll')->assertForbidden();
    }

    public function test_admin_pages_render(): void
    {
        $this->withSession(['demo_role' => 'admin'])->get('/administration')->assertOk()->assertSee('Users and verification');
        $this->get('/workspace')->assertOk();
    }

    public function test_profile_update_does_not_allow_role_escalation(): void
    {
        $this->post('/profile', ['full_name' => 'Updated Member', 'role' => 'admin'])->assertRedirect();
        $this->get('/profile')->assertSee('Updated Member');
        $this->get('/administration')->assertForbidden();
    }

    public function test_demo_switch_is_unavailable_in_live_mode(): void
    {
        config(['komuniedad.demo' => false]);
        $this->post('/demo/role', ['role' => 'admin'])->assertNotFound();
    }

    public function test_activity_validation_and_save(): void
    {
        $this->withSession(['demo_role' => 'coordinator'])->post('/workspace/create', [])->assertSessionHasErrors(['title', 'capacity', 'start_at']);
        $this->post('/workspace/create', ['title' => 'New program', 'description' => 'Meet your neighbors', 'venue' => 'Hall', 'category_id' => 'social', 'start_at' => now()->addDays(3)->toDateTimeString(), 'end_at' => now()->addDays(3)->addHour()->toDateTimeString(), 'cutoff_at' => now()->addDays(2)->toDateTimeString(), 'capacity' => 10, 'status' => 'open'])->assertRedirect('/workspace');
        $this->get('/workspace')->assertSee('New program');
    }

    public function test_registration_only_sends_senior_profile_metadata(): void
    {
        config(['komuniedad.demo' => false, 'komuniedad.url' => 'https://example.test', 'komuniedad.key' => 'test']);
        Http::fake(['*' => Http::response(['user' => ['id' => 'new-user']])]);
        $this->post('/register', ['full_name' => 'New Member', 'email' => 'member@example.test', 'password' => 'A-long-password-123', 'password_confirmation' => 'A-long-password-123', 'role' => 'admin'])->assertRedirect('/login');
        Http::assertSent(fn ($r) => $r['data'] === ['full_name' => 'New Member'] && ! isset($r['role']));
    }

    public function test_feedback_requires_completed_attendance(): void
    {
        $this->post('/activities/1/enroll');
        $id = session('demo_enrollments')[0]['enrollment_id'];
        $this->post('/history/'.$id.'/feedback', ['rating' => 5])->assertSessionHasErrors('feedback');
    }

    public function test_announcement_is_saved_and_escaped(): void
    {
        $this->withSession(['demo_role' => 'coordinator'])->post('/announcements', ['title' => 'Notice', 'message' => '<script>alert(1)</script>', 'activity_id' => '1'])->assertRedirect();
        $this->get('/announcements')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_database_rpc_scalar_response_is_supported(): void
    {
        config(['komuniedad.url' => 'https://example.test', 'komuniedad.key' => 'test']);
        Http::fake(['*' => Http::response('"new-activity-id"', 200, ['Content-Type' => 'application/json'])]);
        $this->assertSame('new-activity-id', app(Community::class)->rpc('save_activity', ['payload' => []]));
    }
    public function test_login_and_register_are_public_pages_without_authenticated_navigation(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('Good to see you.')
            ->assertDontSee('Discover activities')
            ->assertDontSee('data-sidebar-toggle', false);

        $this->get('/register')->assertOk()
            ->assertSee('Create a senior account')
            ->assertDontSee('Discover activities')
            ->assertDontSee('data-sidebar-toggle', false);
    }

    public function test_demo_registration_and_login_work_end_to_end(): void
    {
        $payload = [
            'full_name' => 'Demo New Member',
            'email' => 'demo.member@example.test',
            'password' => 'A-long-password-123',
            'password_confirmation' => 'A-long-password-123',
        ];

        $this->post('/register', $payload)->assertRedirect('/login')
            ->assertSessionHas('demo_registered_account');

        $this->post('/login', [
            'email' => $payload['email'],
            'password' => $payload['password'],
        ])->assertRedirect('/');

        $this->get('/profile')->assertOk()->assertSee('Demo New Member');
    }

    public function test_demo_registration_rejects_invalid_and_duplicate_accounts(): void
    {
        $this->post('/register', [
            'full_name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['full_name', 'email', 'password']);

        $payload = [
            'full_name' => 'Demo Member',
            'email' => 'duplicate@example.test',
            'password' => 'A-long-password-123',
            'password_confirmation' => 'A-long-password-123',
        ];

        $this->post('/register', $payload)->assertRedirect('/login');
        $this->post('/register', $payload)->assertSessionHasErrors('email');
    }

    public function test_live_registration_never_inherits_admin_session_or_auto_signs_in(): void
    {
        config(['komuniedad.demo' => false, 'komuniedad.url' => 'https://example.test', 'komuniedad.key' => 'test']);
        Http::fake(['*' => Http::response(['user' => ['id' => 'new-user'], 'access_token' => 'new-token'])]);

        $this->withSession(['access_token' => 'old-admin-token', 'profile' => ['role' => 'admin']])
            ->post('/register', [
                'full_name' => 'New Member',
                'email' => 'member@example.test',
                'password' => 'A-long-password-123',
                'password_confirmation' => 'A-long-password-123',
            ])->assertRedirect('/login')
            ->assertSessionMissing('access_token')
            ->assertSessionMissing('profile');

        Http::assertSent(fn ($request) =>
            str_contains($request->url(), '/auth/v1/signup')
            && ! $request->hasHeader('Authorization')
            && ($request['data']['full_name'] ?? null) === 'New Member'
            && ! isset($request['data']['role'])
        );
    }

    public function test_live_registration_explains_default_smtp_restriction(): void
    {
        config(['komuniedad.demo' => false, 'komuniedad.url' => 'https://example.test', 'komuniedad.key' => 'test']);
        Http::fake(['*' => Http::response([
            'code' => 'email_address_not_authorized',
            'message' => 'Email address not authorized',
        ], 400)]);

        $this->post('/register', [
            'full_name' => 'New Member',
            'email' => 'member@real-domain.test',
            'password' => 'A-long-password-123',
            'password_confirmation' => 'A-long-password-123',
        ])->assertSessionHasErrors('service');
    }

    public function test_live_registration_explains_profile_trigger_failure(): void
    {
        config(['komuniedad.demo' => false, 'komuniedad.url' => 'https://example.test', 'komuniedad.key' => 'test']);
        Http::fake(['*' => Http::response([
            'code' => 'unexpected_failure',
            'message' => 'Database error saving new user',
        ], 500)]);

        $this->post('/register', [
            'full_name' => 'New Member',
            'email' => 'member@example.test',
            'password' => 'A-long-password-123',
            'password_confirmation' => 'A-long-password-123',
        ])->assertSessionHasErrors('service');
    }

    public function test_paid_activity_is_explicitly_cash_only_and_attendance_waits_for_payment(): void
    {
        $this->withSession(['demo_role' => 'senior'])
            ->post('/activities/3/enroll')
            ->assertRedirect();

        $enrollment = session('demo_enrollments')[0];
        $this->assertSame('unpaid', $enrollment['payment_status']);

        $activities = session('demo_activities');
        foreach ($activities as &$activity) {
            if ($activity['activity_id'] === '3') {
                $activity['status'] = 'ongoing';
                $activity['start_at'] = now()->subHour()->toIso8601String();
                $activity['end_at'] = now()->addHour()->toIso8601String();
            }
        }
        unset($activity);

        $this->withSession(['demo_role' => 'coordinator', 'demo_activities' => $activities])
            ->post('/workspace/3/attendance', [
                'enrollment_id' => $enrollment['enrollment_id'],
                'attended' => 1,
            ])->assertSessionHasErrors('attendance');

        $this->post('/workspace/3/payment', [
            'enrollment_id' => $enrollment['enrollment_id'],
            'paid' => 1,
            'reason' => 'Cash received at the front desk',
        ])->assertRedirect();

        $this->assertSame('paid', session('demo_enrollments')[0]['payment_status']);

        $this->post('/workspace/3/attendance', [
            'enrollment_id' => $enrollment['enrollment_id'],
            'attended' => 1,
        ])->assertRedirect();
    }

}
