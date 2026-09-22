<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class Community
{
    public function demo(): bool
    {
        return config('komuniedad.demo') && app()->environment('local', 'testing');
    }

    public function api(string $method, string $path, array $data = []): mixed
    {
        abort_unless(config('komuniedad.url') && config('komuniedad.key'), 503, 'Supabase is not configured.');
        try {
            $r = Http::baseUrl(rtrim(config('komuniedad.url'), '/'))->timeout(15)->withHeaders(['apikey' => config('komuniedad.key')])->withToken(session('access_token', config('komuniedad.key')))->send($method, $path, $method === 'GET' ? ['query' => $data] : ['json' => (object) $data]);
        } catch (ConnectionException $e) {
            throw ValidationException::withMessages(['service' => 'Service unavailable. Please try again.']);
        }
        if ($r->failed()) {
            throw ValidationException::withMessages(['service' => 'Request unsuccessful. Check your account, activity status, or existing enrollment.']);
        }

        return $r->json() ?? [];
    }

    public function activities(): array
    {
        if (! $this->demo()) {
            $counts = collect($this->api('POST', '/rest/v1/rpc/activity_counts'))->keyBy('activity_id');
            $coordinators = collect($this->api('POST', '/rest/v1/rpc/coordinator_directory'))->keyBy('user_id');

            return array_map(fn ($a) => $a + ['confirmed' => $counts[$a['activity_id']]['confirmed'] ?? 0, 'coordinator_name' => $coordinators[$a['coordinator_id']]['full_name'] ?? 'Community coordinator'], $this->api('GET', '/rest/v1/activities', ['select' => '*,categories(name)', 'order' => 'start_at.asc']));
        }
        if (session()->has('demo_activities')) {
            return session('demo_activities');
        }
        $activities = collect([
            ['Morning movement & gentle stretching', 'Wellness', 'Community garden', 18, 24, 'movement', 'Start your day with guided, low-impact stretches and good company. Bring water and comfortable shoes.'],
            ['Grow together: urban gardening', 'Learning', 'Barangay learning center', 8, 15, 'garden', 'Learn to grow herbs and vegetables in small spaces. Share your gardening stories. Materials are provided.'],
            ['Kwentuhan & coffee afternoon', 'Social', 'Senior citizens hall', 30, 30, 'coffee', 'Good stories, warm coffee, and familiar faces. Join a relaxed afternoon of conversation and community.'],
            ['Digital basics: stay connected', 'Learning', 'Community computer room', 7, 12, 'digital', 'Practice video calls and everyday phone skills at your own pace. Bring your phone if you have one.'],
        ])->map(fn ($a, $i) => ['activity_id' => (string) ($i + 1), 'coordinator_id' => 'demo-coordinator', 'coordinator_name' => 'Alex Reyes', 'category_id' => strtolower($a[1]), 'title' => $a[0], 'categories' => ['name' => $a[1]], 'venue' => $a[2], 'confirmed' => $a[3], 'capacity' => $a[4], 'art' => $a[5], 'description' => $a[6], 'requirements' => 'Comfortable clothing and drinking water.', 'start_at' => now()->addDays($i + 2)->setTime(9 + $i, 0)->toIso8601String(), 'end_at' => now()->addDays($i + 2)->setTime(11 + $i, 0)->toIso8601String(), 'cutoff_at' => now()->addDays($i + 1)->toIso8601String(), 'status' => 'open'])->all();
        session(['demo_activities' => $activities]);

        return $activities;
    }

    public function enrollments(): array
    {
        return $this->demo() ? session('demo_enrollments', []) : $this->api('GET', '/rest/v1/enrollments', ['select' => '*', 'senior_id' => 'eq.'.session('profile.user_id')]);
    }

    public function role(): string
    {
        return $this->demo() ? session('demo_role', 'senior') : session('profile.role', 'senior');
    }

    public function table(string $table, array $filters = []): array
    {
        if (! $this->demo()) {
            return $this->api('GET', '/rest/v1/'.$table, ['select' => '*'] + $filters);
        }
        if ($table === 'categories') {
            return session('demo_categories', array_map(fn ($c) => ['category_id' => strtolower($c), 'name' => $c, 'description' => 'Community '.strtolower($c).' activities', 'is_active' => true], ['Health', 'Wellness', 'Social', 'Learning', 'Community']));
        }
        if ($table === 'profiles') {
            return session('demo_profiles', [
                ['user_id' => 'demo-senior', 'full_name' => 'Maria Santos', 'role' => 'senior', 'account_status' => 'active', 'contact_number' => ''],
                ['user_id' => 'demo-coordinator', 'full_name' => 'Alex Reyes', 'role' => 'coordinator', 'account_status' => 'active', 'contact_number' => ''],
                ['user_id' => 'demo-admin', 'full_name' => 'Sam Cruz', 'role' => 'admin', 'account_status' => 'active', 'contact_number' => '']]);
        }
        if ($table === 'announcements') {
            return session('demo_announcements', [['announcement_id' => 'notice-1', 'title' => 'Welcome to KomuniEdad', 'message' => 'Explore community activities and find something you enjoy. Your coordinator is here to help.', 'activity_id' => null, 'posted_at' => now()->toIso8601String(), 'archived_at' => null]]);
        }

        return session('demo_'.$table, []);
    }

    public function rpc(string $name, array $data = []): mixed
    {
        return $this->api('POST','/rest/v1/rpc/'.$name,$data);
    }
}
