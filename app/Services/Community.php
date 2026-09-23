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
            $client = Http::baseUrl(rtrim(config('komuniedad.url'), '/'))->timeout(15)->withHeaders(['apikey' => config('komuniedad.key')]);
            if (session('access_token')) {
                $client = $client->withToken(session('access_token'));
            } elseif (str_starts_with(config('komuniedad.key'), 'eyJ')) {
                $client = $client->withToken(config('komuniedad.key'));
            }
            $r = $client->send($method, $path, $method === 'GET' ? ['query' => $data] : ['json' => (object) $data]);
        } catch (ConnectionException $e) {
            abort(503, 'The data service is unavailable. Your session is preserved. Please try again.');
        }
        if ($r->failed()) {
            if ($r->serverError()) {
                abort(503, 'The data service is temporarily unavailable. Please try again.');
            }
            if ($path === '/auth/v1/user' && in_array($r->status(), [401, 403])) {
                abort(401, 'Please sign in again.');
            }
            if ($r->status() === 429) {
                abort(429, 'Too many requests. Please wait a moment and try again.');
            }
            $message = $r->json('message') ?? $r->json('msg') ?? '';
            // Only expose known domain messages; never return raw database details.
            $safe = ['Registration is closed', 'Withdrawal is closed', 'Activity is full', 'Activity is closed', 'You already joined this activity', 'Not authorized', 'Enrollment not found', 'Active enrollment not found', 'Enrollment is not active'];
            $safe = array_merge($safe, ['Capacity cannot be lower than allocated seats', 'The activity has not started', 'The activity has not ended', 'A finalized activity cannot be reopened', 'Complete or cancel the activity before archiving', 'Published activities cannot return to draft', 'Invalid coordinator', 'Invalid category', 'A correction reason is required', 'Attendance is not available for this enrollment', 'Ask another administrator to change your account', 'Reassign active activities before changing this coordinator', 'An active senior account is required']);
            $friendly = in_array($message, $safe, true) ? $message.'.' : 'Request unsuccessful. Check the activity rules, your permissions and the values entered.';
            if (str_starts_with($path, '/auth/v1/token')) {
                $friendly = 'Sign-in failed. Check your email and password, and confirm your email if required.';
            }
            throw ValidationException::withMessages(['service' => $friendly]);
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

    public function saveDemoEnrollments(array $before, array $after, ?string $promoteActivity = null, ?string $excluded = null): void
    {
        $activities = $this->activities();
        foreach ($activities as &$activity) {
            $count = fn ($rows) => collect($rows)->filter(fn ($e) => $e['activity_id'] === $activity['activity_id'] && in_array($e['status'], ['confirmed', 'completed']))->count();
            $activity['confirmed'] = max(0, $activity['confirmed'] + $count($after) - $count($before));
            if ($activity['activity_id'] === $promoteActivity && in_array($activity['status'], ['open', 'full', 'ongoing'])) {
                $waiting = collect($after)->filter(fn ($e) => $e['activity_id'] === $promoteActivity && $e['status'] === 'waitlisted' && $e['enrollment_id'] !== $excluded)->sortBy('enrolled_at');
                foreach ($waiting as $key => $entry) {
                    if ($activity['confirmed'] >= $activity['capacity']) break;
                    $after[$key]['status'] = 'confirmed';
                    $activity['confirmed']++;
                }
            }
        }
        unset($activity);
        session(['demo_enrollments' => $after, 'demo_activities' => $activities]);
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
