<?php

namespace Tests\Feature;

use App\Services\Community;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Phase4TestCase;

class Phase4AuthorizationTest extends Phase4TestCase
{
    public static function roleMatrix(): array
    {
        return [
            'AUTHZ-01' => ['senior','GET','/workspace',403], 'AUTHZ-02' => ['senior','GET','/workspace/create',403],
            'AUTHZ-03' => ['senior','GET','/reports',403], 'AUTHZ-04' => ['senior','GET','/administration',403],
            'AUTHZ-05' => ['senior','POST','/administration/users/demo-senior',403],
            'AUTHZ-06' => ['coordinator','GET','/workspace',200], 'AUTHZ-07' => ['coordinator','GET','/administration',403],
            'AUTHZ-08' => ['coordinator','POST','/administration/users/demo-senior',403], 'AUTHZ-09' => ['coordinator','POST','/administration/categories',403],
            'AUTHZ-10' => ['coordinator','GET','/workspace/1/edit',200], 'AUTHZ-11' => ['coordinator','POST','/workspace/2/edit',404],
            'AUTHZ-12' => ['coordinator','GET','/workspace/2/participants',404], 'AUTHZ-13' => ['coordinator','POST','/workspace/2/attendance',404],
            'AUTHZ-14' => ['coordinator','POST','/workspace/2/payment',404], 'AUTHZ-15' => ['coordinator','POST','/workspace/2/enrollment',404],
            'AUTHZ-21' => ['admin','GET','/administration',200], 'AUTHZ-24' => ['admin','GET','/workspace/2/edit',200],
            'AUTHZ-26' => ['coordinator','POST','/workspace/nonexistent/edit',404],
        ];
    }
    #[DataProvider('roleMatrix')]
    public function test_role_and_owner_matrix(string $role, string $method, string $url, int $status): void
    {
        $activities = app(Community::class)->activities();
        $activities[1]['coordinator_id'] = 'other-coordinator';
        $this->withSession(['demo_role' => $role, 'demo_activities' => $activities])->json($method, $url)->assertStatus($status);
    }
    public function test_AUTHZ_16_cannot_retarget_notice(): void
    {
        $a = app(Community::class)->activities(); $a[1]['coordinator_id'] = 'other-coordinator';
        $this->withSession(['demo_role' => 'coordinator', 'demo_activities' => $a, 'demo_announcements' => [['announcement_id' => 'notice', 'activity_id' => '2']]])
            ->postJson('/announcements', ['announcement_id' => 'notice', 'activity_id' => '1', 'title' => 'Forged', 'message' => 'Forged'])->assertNotFound();
        $this->assertSame('2', session('demo_announcements')[0]['activity_id']);
    }
    private function otherEnrollment(): array { return ['enrollment_id' => 'other-entry', 'senior_id' => 'other-senior', 'activity_id' => '1', 'status' => 'completed', 'attended' => true, 'enrolled_at' => now()->toIso8601String()]; }
    public function test_AUTHZ_17_private_history(): void { $this->withSession(['demo_enrollments' => [$this->otherEnrollment()]])->get('/history')->assertOk()->assertDontSee('Morning movement'); }
    public function test_AUTHZ_18_private_withdrawal(): void { $this->withSession(['demo_enrollments' => [$this->otherEnrollment()]])->postJson('/enrollments/other-entry/withdraw')->assertNotFound(); }
    public function test_AUTHZ_19_private_feedback(): void { $this->withSession(['demo_enrollments' => [$this->otherEnrollment()]])->postJson('/history/other-entry/feedback', ['rating' => 5])->assertUnprocessable(); }
    public function test_AUTHZ_20_unpublished_details(): void
    {
        $a = app(Community::class)->activities(); $a[0]['status'] = 'draft';
        $this->withSession(['demo_activities' => $a])->get('/activities/1')->assertNotFound();
    }
    public function test_AUTHZ_22_admin_manages_user(): void
    {
        $this->withSession(['demo_role' => 'admin'])->postJson('/administration/users/demo-senior', ['role' => 'senior', 'account_status' => 'active', 'verification_status' => 'verified', 'reason' => 'Synthetic verification'])->assertOk();
        $this->assertSame('verified', session('demo_senior_profiles')[0]['verification_status']);
    }
    public function test_AUTHZ_23_admin_manages_category(): void
    {
        $this->withSession(['demo_role' => 'admin'])->postJson('/administration/categories', ['name' => 'Synthetic category', 'is_active' => 1])->assertOk();
        $this->assertTrue(collect(session('demo_categories'))->contains('name', 'Synthetic category'));
    }
    // AUTHZ-25 audit persistence is tested against PostgreSQL in domain.mjs.
    public function test_AUTHZ_27_registration_stays_senior(): void
    {
        $this->post('/register', $this->registration() + ['role' => 'admin'])->assertRedirect('/login');
        $this->get('/administration')->assertForbidden();
    }
    public function test_AUTHZ_28_profile_cannot_promote(): void
    {
        $this->postJson('/profile', ['full_name' => 'Synthetic Senior', 'role' => 'admin', 'account_status' => 'active'])->assertOk();
        $this->get('/administration')->assertForbidden();
    }
    public function test_AUTHZ_29_disabled(): void { $this->live('admin', 'disabled'); $this->postJson('/administration/categories', ['name' => 'Forged', 'is_active' => 1])->assertForbidden(); }
    public function test_AUTHZ_30_guest(): void
    {
        config(['komuniedad.demo' => false]);
        foreach (['/profile', '/workspace', '/administration', '/history', '/reports'] as $url) $this->getJson($url)->assertUnauthorized();
        $this->postJson('/administration/categories', ['name' => 'Forged'])->assertUnauthorized();
    }
}
