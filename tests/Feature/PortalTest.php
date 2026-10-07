<?php

namespace Tests\Feature;

use App\Services\Community;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;
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
            ->assertSee('KomuniEdad')
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
            ->post('/activities/2/enroll')
            ->assertRedirect();

        $enrollment = session('demo_enrollments')[0];
        $this->assertSame('unpaid', $enrollment['payment_status']);

        $activities = session('demo_activities');
        foreach ($activities as &$activity) {
            if ($activity['activity_id'] === '2') {
                $activity['status'] = 'ongoing';
                $activity['start_at'] = now()->subHour()->toIso8601String();
                $activity['end_at'] = now()->addHour()->toIso8601String();
            }
        }
        unset($activity);

        $this->withSession(['demo_role' => 'coordinator', 'demo_activities' => $activities])
            ->post('/workspace/2/attendance', [
                'enrollment_id' => $enrollment['enrollment_id'],
                'attended' => 1,
            ])->assertSessionHasErrors('attendance');

        $this->post('/workspace/2/payment', [
            'enrollment_id' => $enrollment['enrollment_id'],
            'paid' => 1,
            'reason' => 'Cash received at the front desk',
        ])->assertRedirect();

        $this->assertSame('paid', session('demo_enrollments')[0]['payment_status']);

        $this->post('/workspace/2/attendance', [
            'enrollment_id' => $enrollment['enrollment_id'],
            'attended' => 1,
        ])->assertRedirect();
    }


    public function test_profile_photo_upload_uses_authenticated_owned_storage_path(): void
    {
        config([
            'komuniedad.demo' => false,
            'komuniedad.url' => 'https://example.test',
            'komuniedad.key' => 'publishable-key',
        ]);

        Http::fake([
            'https://example.test/storage/v1/object/profile-photos/*' => Http::response([], 200),
        ]);

        $userId = '00000000-0000-4000-8000-000000000001';
        $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');

        $this->withSession(['access_token' => 'user-access-token']);

        $path = app(Community::class)->uploadProfilePhoto($file, $userId);

        $this->assertSame($userId.'/avatar', $path);
        $this->assertSame(
            'https://example.test/storage/v1/object/public/profile-photos/'.$userId.'/avatar',
            app(Community::class)->profilePhotoUrl($path)
        );

        Http::assertSent(fn ($request) =>
            $request->url() === 'https://example.test/storage/v1/object/profile-photos/'.$userId.'/avatar'
            && $request->hasHeader('Authorization', 'Bearer user-access-token')
            && $request->hasHeader('x-upsert', 'true')
        );
    }


    public function test_admin_reports_render_charts_filters_and_csv_export(): void
    {
        $this->withSession(['demo_role' => 'admin'])
            ->get('/reports')
            ->assertOk()
            ->assertSee('data-report-chart', false)
            ->assertSee('name="from"', false)
            ->assertSee('name="to"', false)
            ->assertSee('Export CSV');

        $response = $this->get('/reports/export?status=open');
        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));
    }

    public function test_coordinator_cannot_export_admin_report_csv(): void
    {
        $this->withSession(['demo_role' => 'coordinator'])
            ->get('/reports/export')
            ->assertForbidden();
    }


    public function test_staff_can_delete_authorized_announcement_in_demo(): void
    {
        $this->withSession([
            'demo_role' => 'coordinator',
            'demo_announcements' => [[
                'announcement_id' => 'notice-delete',
                'title' => 'Temporary notice',
                'message' => 'Temporary message',
                'activity_id' => '1',
                'posted_at' => now()->toIso8601String(),
                'archived_at' => null,
            ]],
        ])->post('/announcements/notice-delete/delete')->assertRedirect();

        $this->assertFalse(
            collect(session('demo_announcements', []))->contains('announcement_id', 'notice-delete')
        );
    }

    public function test_activity_cards_expose_full_card_navigation(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-card-href="/activities/1"', false);

        $this->withSession(['demo_role' => 'coordinator'])
            ->get('/workspace')
            ->assertOk()
            ->assertSee('data-card-href="/workspace/1/edit"', false);
    }


    public function test_activity_tags_render_filter_and_save_in_demo(): void
    {
        $this->get('/?tag=gentle')
            ->assertOk()
            ->assertSee('Morning movement & gentle stretching')
            ->assertDontSee('Grow together: urban gardening');

        $activity = app(Community::class)->activities()[0];

        $this->withSession(['demo_role' => 'coordinator'])
            ->post('/workspace/1/edit', [
                'title' => $activity['title'],
                'description' => $activity['description'],
                'venue' => $activity['venue'],
                'category_id' => $activity['category_id'],
                'start_at' => $activity['start_at'],
                'end_at' => $activity['end_at'],
                'cutoff_at' => $activity['cutoff_at'],
                'capacity' => $activity['capacity'],
                'requirements' => $activity['requirements'],
                'status' => $activity['status'],
                'is_free' => 1,
                'fee' => 0,
                'tags' => 'Gentle, Social, gentle',
            ])->assertRedirect('/workspace');

        $saved = collect(session('demo_activities'))->firstWhere('activity_id', '1');
        $this->assertSame(['gentle', 'social'], $saved['tags']);
    }

    public function test_activity_tags_are_limited_to_six_short_values(): void
    {
        $activity = app(Community::class)->activities()[0];

        $this->withSession(['demo_role' => 'coordinator'])
            ->post('/workspace/1/edit', [
                'title' => $activity['title'],
                'description' => $activity['description'],
                'venue' => $activity['venue'],
                'category_id' => $activity['category_id'],
                'start_at' => $activity['start_at'],
                'end_at' => $activity['end_at'],
                'cutoff_at' => $activity['cutoff_at'],
                'capacity' => $activity['capacity'],
                'requirements' => $activity['requirements'],
                'status' => $activity['status'],
                'is_free' => 1,
                'fee' => 0,
                'tags' => 'one,two,three,four,five,six,seven',
            ])->assertSessionHasErrors('tags');
    }

}
