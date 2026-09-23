<?php

namespace App\Http\Controllers;

use App\Services\Community;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CommunityController extends Controller
{
    public function index(Request $r, Community $s)
    {
        if ($s->role() !== 'senior') {
            return redirect('/workspace');
        }
        $r->validate([
            'q' => 'nullable|string|max:160',
            'category' => 'nullable|string|max:80',
            'status' => 'nullable|in:open,full,ongoing,completed,cancelled',
            'mine' => 'nullable|boolean',
        ]);
        $enrollments = $s->ownEnrollments();
        $category = $r->string('category')->toString();
        $query = $r->string('q')->toString();
        $mine = $r->boolean('mine');
        $status = $r->string('status')->toString();
        $activities = array_filter($s->activities(), fn ($a) => (! $category || ($a['categories']['name'] ?? '') === $category) && (! $query || str_contains(strtolower($a['title'].' '.$a['venue']), strtolower($query))) && (! $mine || collect($enrollments)->contains(fn ($e) => $e['activity_id'] === $a['activity_id'] && $e['status'] !== 'cancelled')));
        $activities = array_map(function ($a) {
            if (in_array($a['status'], ['open', 'full'])) {
                $a['status'] = $a['confirmed'] >= $a['capacity'] ? 'full' : 'open';
            }
            return $a;
        }, $activities);
        $activities = array_filter($activities, fn ($a) => ! in_array($a['status'], ['draft', 'archived']) && (! $status || $a['status'] === $status));

        if ($r->expectsJson()) {
            return response()->json([
                'data' => array_values($activities),
                'count' => count($activities),
                'html' => view('activity-results', compact('activities', 'enrollments', 'mine'))->render(),
            ])->header('Cache-Control', 'private, no-store');
        }

        return view('community', compact('activities', 'enrollments', 'category', 'query', 'mine', 'status') + ['demo' => $s->demo(), 'categories' => $s->table('categories')]);
    }

    public function detail(string $id, Community $s)
    {
        $activity = collect($s->activities())->firstWhere('activity_id', $id);
        abort_unless($activity, 404);
        if ($s->role() === 'senior' && in_array($activity['status'], ['draft', 'archived'])) {
            abort_unless(collect($s->ownEnrollments())->contains('activity_id', $id), 404);
        }

        return view('detail', ['activity' => $activity, 'enrollments' => $s->ownEnrollments(), 'demo' => $s->demo()]);
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
            if (collect($s->ownEnrollments())->contains(fn ($e) => $e['activity_id'] === $id && $e['status'] !== 'cancelled')) {
                throw ValidationException::withMessages(['enrollment' => 'You already joined this activity.']);
            }
            $state = $a['confirmed'] < $a['capacity'] ? 'confirmed' : 'waitlisted';
            $entries[] = [
                'enrollment_id' => (string) Str::uuid(),
                'activity_id' => $id,
                'senior_id' => 'demo-senior',
                'status' => $state,
                'payment_status' => ($a['is_free'] ?? true) ? 'not_required' : 'unpaid',
                'enrolled_at' => now()->toIso8601String(),
            ];
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
            $promote = null;
            foreach ($entries as &$e) {
                if ($e['enrollment_id'] === $id && $e['senior_id'] === 'demo-senior' && in_array($e['status'], ['pending', 'confirmed', 'waitlisted'])) {
                    $a = collect($s->activities())->firstWhere('activity_id', $e['activity_id']);
                    if (! $a || ! in_array($a['status'], ['open', 'full']) || Carbon::parse($a['cutoff_at'])->isPast()) {
                        throw ValidationException::withMessages(['enrollment' => 'Withdrawal is closed.']);
                    }
                    $promote = $e['status'] === 'confirmed' ? $e['activity_id'] : null;
                    $e['status'] = 'cancelled';
                    $found = true;
                }
            }
            abort_unless($found, 404);
            unset($e);
            $s->saveDemoEnrollments($s->enrollments(), $entries, $promote);
        } else {
            $s->api('POST', '/rest/v1/rpc/withdraw_enrollment', ['target' => $id]);
        }

        return redirect('/?mine=1')->with('status', 'Your enrollment has been withdrawn.');
    }

    public function login(Request $r, Community $s)
    {
        $d = $r->validate(['email' => 'required|email', 'password' => 'required|string']);

        if ($s->demo()) {
            $account = session('demo_registered_account');
            $matches = $account
                && hash_equals(strtolower($account['email']), strtolower(trim($d['email'])))
                && Hash::check($d['password'], $account['password']);

            if (! $matches) {
                throw ValidationException::withMessages(['email' => 'The demo email or password is incorrect. Create a demo senior account first if needed.']);
            }

            $r->session()->regenerate();
            session(['demo_role' => 'senior', 'demo_authenticated' => true]);

            return redirect('/')->with('status', 'Signed in successfully.');
        }

        $a = $s->api('POST', '/auth/v1/token?grant_type=password', $d);
        if (empty($a['access_token'])) {
            throw ValidationException::withMessages(['email' => 'Sign-in could not be confirmed. Please try again.']);
        }

        $r->session()->regenerate();
        $r->session()->forget('access_token');

        $cookie = cookie(
            Community::AUTH_COOKIE,
            $a['access_token'],
            (int) config('session.lifetime', 120),
            '/',
            config('session.domain'),
            (bool) config('session.secure', false),
            true,
            false,
            config('session.same_site', 'lax')
        );

        return redirect('/')->withCookie($cookie);
    }
}
