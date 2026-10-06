<?php

namespace Tests;

use App\Services\Community;
use Illuminate\Support\Facades\Http;

abstract class Phase4TestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['komuniedad.demo' => true, 'app.debug' => false]);
        Http::preventStrayRequests();
    }

    protected function registration(): array
    {
        return ['full_name' => 'Synthetic Senior', 'email' => 'senior@example.test', 'password' => 'Synthetic-only-123', 'password_confirmation' => 'Synthetic-only-123'];
    }

    protected function activityPayload(): array
    {
        return ['title' => 'Synthetic activity', 'description' => 'Test description', 'venue' => 'Test hall', 'category_id' => 'social', 'coordinator_id' => 'demo-coordinator', 'start_at' => now()->addDays(3)->toIso8601String(), 'end_at' => now()->addDays(3)->addHour()->toIso8601String(), 'cutoff_at' => now()->addDays(2)->toIso8601String(), 'capacity' => 10, 'status' => 'open', 'is_free' => 1, 'fee' => 0];
    }

    protected function live(string $role = 'senior', string $status = 'active'): void
    {
        config(['komuniedad.demo' => false, 'komuniedad.url' => 'https://supabase.example.test', 'komuniedad.key' => 'synthetic-publishable-key']);
        $this->withSession(['access_token' => 'synthetic-token']);
        Http::fake(function ($r) use ($role, $status) {
            if (str_contains($r->url(), '/auth/v1/user')) return Http::response(['id' => '00000000-0000-4000-8000-000000000001']);
            if (str_contains($r->url(), '/rest/v1/profiles')) return Http::response([['user_id' => '00000000-0000-4000-8000-000000000001', 'full_name' => 'Synthetic Senior', 'role' => $role, 'account_status' => $status]]);
            return Http::response([]);
        });
    }

    protected function completedEnrollment(): string
    {
        $this->postJson('/activities/1/enroll')->assertOk();
        $entries = session('demo_enrollments');
        $entries[0]['status'] = 'completed';
        $entries[0]['attended'] = true;
        $activities = session('demo_activities');
        $activities[0]['status'] = 'completed';
        $this->withSession(['demo_enrollments' => $entries, 'demo_activities' => $activities]);
        return $entries[0]['enrollment_id'];
    }
}
