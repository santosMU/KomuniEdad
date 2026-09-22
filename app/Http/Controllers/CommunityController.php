<?php

namespace App\Http\Controllers;

use App\Services\Community;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CommunityController extends Controller
{
    public function index(Request $r, Community $s)
    {
        if ($s->role() !== 'senior') {
            return redirect('/workspace');
        }
        $enrollments = $s->enrollments();
        $category = $r->string('category')->toString();
        $query = $r->string('q')->toString();
        $mine = $r->boolean('mine');
        $status = $r->string('status')->toString();
        $activities = array_filter($s->activities(), fn ($a) => (! $category || ($a['categories']['name'] ?? '') === $category) && (! $query || str_contains(strtolower($a['title'].' '.$a['venue']), strtolower($query))) && (! $mine || collect($enrollments)->contains(fn ($e) => $e['activity_id'] === $a['activity_id'] && $e['status'] !== 'cancelled')));
        $activities = array_filter($activities, fn ($a) => ! in_array($a['status'], ['draft', 'archived']) && (! $status || $a['status'] === $status));

        return view('community', compact('activities', 'enrollments', 'category', 'query', 'mine', 'status') + ['demo' => $s->demo(), 'categories' => $s->table('categories')]);
    }

    public function detail(string $id, Community $s)
    {
        $activity = collect($s->activities())->firstWhere('activity_id', $id);
        abort_unless($activity, 404);

        return view('detail', ['activity' => $activity, 'enrollments' => $s->enrollments(), 'demo' => $s->demo()]);
    }

    public function enroll(string $id, Community $s)
    {
        abort_unless($s->role() === 'senior', 403);
        if ($s->demo()) {
            $a = collect($s->activities())->firstWhere('activity_id', $id);
            abort_unless($a, 404);
            $entries = $s->enrollments();
            if (! in_array($a['status'], ['open', 'full']) || Carbon::parse($a['cutoff_at'])->isPast()) {
                throw ValidationException::withMessages(['enrollment' => 'Registration is closed.']);
            }
            if (collect($entries)->contains(fn ($e) => $e['activity_id'] === $id && $e['status'] !== 'cancelled')) {
                throw ValidationException::withMessages(['enrollment' => 'You already joined this activity.']);
            }
            $state = $a['confirmed'] < $a['capacity'] ? 'confirmed' : 'waitlisted';
            $entries[] = ['enrollment_id' => (string) Str::uuid(), 'activity_id' => $id, 'senior_id' => 'demo-senior', 'status' => $state, 'enrolled_at' => now()->toIso8601String()];
            session(['demo_enrollments' => $entries]);
            if ($state === 'confirmed') {
                $activities = $s->activities();
                foreach ($activities as &$activity) {
                    if ($activity['activity_id'] === $id) {
                        $activity['confirmed']++;
                    }
                }session(['demo_activities' => $activities]);
            }
        } else {
            $s->api('POST', '/rest/v1/rpc/enroll_in_activity', ['target' => $id]);
        }

        return redirect('/?mine=1')->with('status', 'Your enrollment was saved. Your current status is shown below.');
    }

    public function withdraw(string $id, Community $s)
    {
        abort_unless($s->role() === 'senior', 403);
        if ($s->demo()) {
            $entries = $s->enrollments();
            $found = false;
            foreach ($entries as &$e) {
                if ($e['enrollment_id'] === $id && $e['status'] !== 'cancelled') {
                    $a = collect($s->activities())->firstWhere('activity_id', $e['activity_id']);
                    if (! $a || ! in_array($a['status'], ['open', 'full']) || Carbon::parse($a['cutoff_at'])->isPast()) {
                        throw ValidationException::withMessages(['enrollment' => 'Withdrawal is closed.']);
                    }
                    if ($e['status'] === 'confirmed') {
                        $activities = $s->activities();
                        foreach ($activities as &$activity) {
                            if ($activity['activity_id'] === $e['activity_id']) {
                                $activity['confirmed'] = max(0, $activity['confirmed'] - 1);
                            }
                        }session(['demo_activities' => $activities]);
                    }
                    $e['status'] = 'cancelled';
                    $found = true;
                }
            }
            abort_unless($found, 404);
            session(['demo_enrollments' => $entries]);
        } else {
            $s->api('POST', '/rest/v1/rpc/withdraw_enrollment', ['target' => $id]);
        }

        return redirect('/?mine=1')->with('status', 'Your enrollment has been withdrawn.');
    }

    public function login(Request $r, Community $s)
    {
        $d = $r->validate(['email' => 'required|email', 'password' => 'required|string']);
        $a = $s->api('POST', '/auth/v1/token?grant_type=password', $d);
        $r->session()->regenerate();
        session(['access_token' => $a['access_token']]);

        return redirect('/');
    }
}
