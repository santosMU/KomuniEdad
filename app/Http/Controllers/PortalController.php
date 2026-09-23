<?php

namespace App\Http\Controllers;

use App\Services\Community;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PortalController extends Controller
{
    private function staff(Community $s): void
    {
        abort_unless(in_array($s->role(), ['coordinator', 'admin']), 403);
    }

    private function admin(Community $s): void
    {
        abort_unless($s->role() === 'admin', 403);
    }

    private function activities(Community $s): array
    {
        return array_values(array_filter($s->activities(), fn ($a) => $s->role() === 'admin' || $a['coordinator_id'] === ($s->demo() ? 'demo-coordinator' : session('profile.user_id'))));
    }

    private function activity(string $id, Community $s): array
    {
        $this->staff($s);
        $a = collect($this->activities($s))->firstWhere('activity_id', $id);
        abort_unless($a, 404);

        return $a;
    }

    private function saveDemo(string $table, string $key, array $row, Community $s): void
    {
        $rows = $s->table($table);
        $rows = array_values(array_filter($rows, fn ($r) => $r[$key] !== $row[$key]));
        $rows[] = $row;
        session(['demo_'.$table => $rows]);
    }

    private function participants(string $id, Community $s): array
    {
        if (! $s->demo()) {
            return $s->rpc('participant_list', ['target' => $id]);
        }

        return array_values(array_map(fn ($e) => $e + ['senior_id' => 'demo-senior', 'full_name' => 'Maria Santos', 'attended' => null, 'remarks' => ''], array_filter($s->enrollments(), fn ($e) => $e['activity_id'] === $id)));
    }

    public function workspace(Community $s)
    {
        $this->staff($s);
        $activities = $this->activities($s);

        return view('workspace', compact('activities') + ['demo' => $s->demo(), 'role' => $s->role()]);
    }

    public function edit(Community $s, ?string $id = null)
    {
        $this->staff($s);
        $activity = $id ? $this->activity($id, $s) : null;
        $coordinators = $s->demo() ? array_filter($s->table('profiles'), fn ($p) => in_array($p['role'], ['coordinator', 'admin'])) : $s->rpc('coordinator_directory');

        return view('activity-form', ['activity' => $activity, 'categories' => $s->table('categories'), 'coordinators' => $coordinators, 'demo' => $s->demo(), 'role' => $s->role()]);
    }

    public function save(Request $r, Community $s, ?string $id = null)
    {
        $this->staff($s);
        if ($id) {
            $this->activity($id, $s);
        }
        $d = $r->validate(['title' => 'required|string|max:160', 'description' => 'required|string|max:5000', 'venue' => 'required|string|max:200', 'category_id' => 'required|string', 'coordinator_id' => 'nullable|string', 'start_at' => 'required|date', 'end_at' => 'required|date|after:start_at', 'cutoff_at' => 'required|date|before_or_equal:start_at', 'capacity' => 'required|integer|min:1|max:10000', 'requirements' => 'nullable|string|max:2000', 'status' => ['required', Rule::in(['draft', 'open', 'full', 'ongoing', 'completed', 'cancelled', 'archived'])]]);
        foreach (['start_at', 'end_at', 'cutoff_at'] as $key) {
            $d[$key] = Carbon::parse($d[$key], config('app.timezone'))->toIso8601String();
        }
        if ($s->demo()) {
            $old = $id ? $this->activity($id, $s) : null;
            $category = collect($s->table('categories'))->firstWhere('category_id', $d['category_id']);
            if (! $category || ! $category['is_active']) {
                throw ValidationException::withMessages(['category_id' => 'Choose an active category.']);
            }
            if ($old && $old['status'] !== 'draft' && $d['status'] === 'draft') {
                throw ValidationException::withMessages(['status' => 'Published activities cannot return to draft.']);
            }
            if ($s->role() === 'coordinator') {
                $d['coordinator_id'] = 'demo-coordinator';
            }
            if ($old && $d['capacity'] < $old['confirmed']) {
                throw ValidationException::withMessages(['capacity' => 'Capacity cannot be lower than allocated seats.']);
            }
            if (! $old && ! in_array($d['status'], ['draft', 'open'])) {
                throw ValidationException::withMessages(['status' => 'Create activities as draft or open.']);
            }
            if ($old && in_array($old['status'], ['completed', 'cancelled', 'archived']) && ! in_array($d['status'], [$old['status'], 'archived'])) {
                throw ValidationException::withMessages(['status' => 'Finalized activities cannot be reopened.']);
            }
            if ($d['status'] === 'archived' && ! in_array($old['status'] ?? '', ['completed', 'cancelled', 'archived'])) {
                throw ValidationException::withMessages(['status' => 'Complete or cancel before archiving.']);
            }
            if (in_array($d['status'], ['ongoing', 'completed']) && Carbon::parse($d['start_at'])->isFuture()) {
                throw ValidationException::withMessages(['status' => 'The activity has not started.']);
            }
            if ($d['status'] === 'completed' && Carbon::parse($d['end_at'])->isFuture()) {
                throw ValidationException::withMessages(['status' => 'The activity has not ended.']);
            }
            $d['activity_id'] = $id ?? (string) Str::uuid();
            $d['coordinator_id'] = $d['coordinator_id'] ?? 'demo-coordinator';
            $d['confirmed'] = $old['confirmed'] ?? 0;
            $d['categories'] = ['name' => collect($s->table('categories'))->firstWhere('category_id', $d['category_id'])['name'] ?? 'Community'];
            $rows = array_values(array_filter($s->activities(), fn ($a) => $a['activity_id'] !== $d['activity_id']));
            $rows[] = $d;
            session(['demo_activities' => $rows]);
            if ($old && $d['capacity'] > $old['capacity'] && in_array($d['status'], ['open', 'full']) && Carbon::parse($d['cutoff_at'])->isFuture()) {
                $s->saveDemoEnrollments($s->enrollments(), $s->enrollments(), $d['activity_id']);
            }
            if (in_array($d['status'], ['cancelled', 'completed'])) {
                $entries = $s->enrollments();
                foreach ($entries as &$e) {
                    if ($e['activity_id'] === $d['activity_id'] && in_array($e['status'], ['pending', 'confirmed', 'waitlisted'])) {
                        $e['status'] = $d['status'] === 'completed' && $e['status'] === 'confirmed' ? 'completed' : 'cancelled';
                    }
                }
                unset($e);
                $s->saveDemoEnrollments($s->enrollments(), $entries);
            }
        } else {
            $s->rpc('save_activity', ['payload' => $d, 'target' => $id]);
        }

        return redirect('/workspace')->with('status', 'Activity saved.');
    }

    public function roster(string $id, Community $s)
    {
        return view('roster', ['activity' => $this->activity($id, $s), 'participants' => $this->participants($id, $s), 'demo' => $s->demo()]);
    }

    public function attendance(Request $r, string $id, Community $s)
    {
        $a = $this->activity($id, $s);
        $d = $r->validate(['enrollment_id' => 'required|string', 'attended' => 'required|boolean', 'remarks' => 'nullable|string|max:1000']);
        abort_unless(collect($this->participants($id, $s))->contains('enrollment_id', $d['enrollment_id']), 404);
        if ($s->demo()) {
            if (! in_array($a['status'], ['ongoing', 'completed'])) {
                throw ValidationException::withMessages(['attendance' => 'Start or complete the activity before recording attendance.']);
            }
            $entry = collect($s->enrollments())->firstWhere('enrollment_id', $d['enrollment_id']);
            if (! in_array($entry['status'], ['confirmed', 'completed'])) {
                throw ValidationException::withMessages(['attendance' => 'Attendance requires a confirmed or completed enrollment.']);
            }
            if ($s->role() === 'admin' && isset($entry['attended']) && strlen(trim($d['remarks'] ?? '')) < 3) {
                throw ValidationException::withMessages(['remarks' => 'Explain the attendance correction.']);
            }
            $rows = $s->enrollments();
            foreach ($rows as &$e) {
                if ($e['enrollment_id'] === $d['enrollment_id']) {
                    $e['attended'] = (bool) $d['attended'];
                    $e['remarks'] = $d['remarks'] ?? '';
                }
            }
            session(['demo_enrollments' => $rows]);
        } else {
            $s->rpc('record_attendance', ['target' => $d['enrollment_id'], 'present' => (bool) $d['attended'], 'note' => $d['remarks'] ?? '']);
        }

        return back()->with('status', 'Attendance saved.');
    }

    public function profile(Community $s)
    {
        $profile = $s->demo() ? collect($s->table('profiles'))->firstWhere('user_id', 'demo-'.$s->role()) : session('profile');
        $senior = $s->demo() ? session('demo_senior', []) : ($s->table('senior_profiles', ['user_id' => 'eq.'.session('profile.user_id')])[0] ?? []);

        return view('profile', ['profile' => $profile, 'senior' => $senior, 'demo' => $s->demo()]);
    }

    public function enrollment(Request $r, string $id, Community $s)
    {
        $a = $this->activity($id, $s);
        $d = $r->validate(['senior_id' => ['required', $s->demo() ? 'string' : 'uuid'], 'status' => 'required|in:confirmed,waitlisted,cancelled', 'reason' => 'required|string|min:3|max:500']);
        if ($s->demo()) {
            if (! in_array($a['status'], ['open', 'full', 'ongoing'])) {
                throw ValidationException::withMessages(['enrollment' => 'Activity is closed.']);
            }
            $existing = collect($s->enrollments())->first(fn ($e) => $e['activity_id'] === $id && $e['senior_id'] === $d['senior_id'] && $e['status'] !== 'cancelled');
            if ($d['status'] === 'confirmed' && ($existing['status'] ?? '') !== 'confirmed' && $a['confirmed'] >= $a['capacity']) {
                throw ValidationException::withMessages(['enrollment' => 'Activity is full.']);
            }
            $rows = $s->enrollments();
            $found = false;
            foreach ($rows as &$e) {
                if ($e['activity_id'] === $id && ($e['senior_id'] ?? 'demo-senior') === $d['senior_id'] && $e['status'] !== 'cancelled') {
                    $e['status'] = $d['status'];
                    $found = true;
                }
            }
            unset($e);
            if (! $found) {
                if ($d['status'] === 'cancelled') {
                    throw ValidationException::withMessages(['enrollment' => 'No active enrollment found.']);
                }
                $rows[] = ['enrollment_id' => (string) Str::uuid(), 'activity_id' => $id, 'senior_id' => $d['senior_id'], 'status' => $d['status'], 'enrolled_at' => now()->toIso8601String()];
            }
            $s->saveDemoEnrollments($s->enrollments(), $rows, ($existing['status'] ?? '') === 'confirmed' && $d['status'] !== 'confirmed' ? $id : null, $existing['enrollment_id'] ?? null);
        } else {
            $s->rpc('manage_enrollment', ['activity' => $id, 'senior' => $d['senior_id'], 'new_status' => $d['status'], 'reason' => $d['reason']]);
        }

        return back()->with('status', 'Participant enrollment updated.');
    }

    public function updateProfile(Request $r, Community $s)
    {
        $d = $r->validate(['full_name' => 'required|string|max:120', 'contact_number' => 'nullable|string|max:30', 'birthdate' => 'nullable|date|before_or_equal:today', 'address' => 'nullable|string|max:500']);
        if ($s->demo()) {
            $p = collect($s->table('profiles'))->firstWhere('user_id', 'demo-'.$s->role());
            $this->saveDemo('profiles', 'user_id', array_merge($p, $d), $s);
            session(['demo_senior' => $d]);
        } else {
            $s->rpc('update_own_profile', ['payload' => $d]);
        }

        return back()->with('status', 'Profile updated.');
    }

    public function history(Community $s)
    {
        abort_unless($s->role() === 'senior', 403);

        return view('history', ['enrollments' => $s->ownEnrollments(), 'activities' => collect($s->activities())->keyBy('activity_id'), 'attendance' => collect($s->table('attendance'))->keyBy('enrollment_id'), 'feedback' => collect($s->table('feedback'))->keyBy('enrollment_id'), 'demo' => $s->demo()]);
    }

    public function feedback(Request $r, string $id, Community $s)
    {
        abort_unless($s->role() === 'senior', 403);
        $d = $r->validate(['rating' => 'required|integer|between:1,5', 'comments' => 'nullable|string|max:2000']);
        if ($s->demo()) {
            $e = collect($s->ownEnrollments())->firstWhere('enrollment_id', $id);
            if (! $e || $e['status'] !== 'completed' || ! ($e['attended'] ?? false) || collect($s->table('feedback'))->contains('enrollment_id', $id)) {
                throw ValidationException::withMessages(['feedback' => 'Feedback is available once after a completed activity you attended.']);
            }
            $this->saveDemo('feedback', 'enrollment_id', $d + ['enrollment_id' => $id], $s);
        } else {
            $s->rpc('submit_feedback', ['target' => $id, 'score' => (int) $d['rating'], 'comment' => $d['comments'] ?? '']);
        }

        return back()->with('status', 'Thank you for your feedback.');
    }

    public function announcements(Community $s)
    {
        return view('announcements', ['announcements' => $s->table('announcements'), 'activities' => in_array($s->role(), ['coordinator', 'admin']) ? $this->activities($s) : [], 'demo' => $s->demo(), 'role' => $s->role()]);
    }

    public function announce(Request $r, Community $s)
    {
        $this->staff($s);
        $d = $r->validate(['title' => 'required|string|max:160', 'message' => 'required|string|max:5000', 'activity_id' => 'nullable|string', 'announcement_id' => 'nullable|string', 'archived' => 'nullable|boolean']);
        if ($s->role() === 'coordinator') {
            abort_unless($d['activity_id'] ?? null, 403);
            $this->activity($d['activity_id'], $s);
        }
        if ($s->demo()) {
            if (! empty($d['announcement_id'])) {
                $existing = collect($s->table('announcements'))->firstWhere('announcement_id', $d['announcement_id']);
                abort_unless($existing, 404);
                if ($s->role() === 'coordinator') {
                    abort_unless($existing['activity_id'], 403);
                    $this->activity($existing['activity_id'], $s);
                }
                $d['activity_id'] = $existing['activity_id'];
            }
            $this->saveDemo('announcements', 'announcement_id', array_merge($d, ['announcement_id' => $d['announcement_id'] ?? (string) Str::uuid(), 'posted_at' => now()->toIso8601String(), 'archived_at' => $r->boolean('archived') ? now()->toIso8601String() : null]), $s);
        } else {
            $s->rpc('save_announcement', ['payload' => $d, 'target' => $d['announcement_id'] ?? null]);
        }

        return back()->with('status', 'Announcement saved.');
    }

    public function administration(Community $s)
    {
        $this->admin($s);

        return view('administration', ['users' => $s->table('profiles'), 'seniors' => collect($s->table('senior_profiles'))->keyBy('user_id'), 'categories' => $s->table('categories'), 'logs' => $s->table('audit_logs', ['order' => 'created_at.desc', 'limit' => 100]), 'demo' => $s->demo()]);
    }

    public function user(Request $r, string $id, Community $s)
    {
        $this->admin($s);
        $d = $r->validate(['role' => ['required', Rule::in(['senior', 'coordinator', 'admin'])], 'account_status' => ['required', Rule::in(['active', 'disabled'])], 'verification_status' => ['required', Rule::in(['pending', 'verified', 'rejected'])], 'reason' => 'required|string|min:3|max:500']);
        if ($s->demo()) {
            $p = collect($s->table('profiles'))->firstWhere('user_id', $id);
            abort_unless($p, 404);
            if ($id === 'demo-admin') {
                throw ValidationException::withMessages(['account' => 'Ask another administrator to change your account.']);
            }
            if (($d['role'] !== 'coordinator' || $d['account_status'] === 'disabled') && collect($s->activities())->contains(fn ($a) => $a['coordinator_id'] === $id && !in_array($a['status'], ['completed', 'cancelled', 'archived']))) {
                throw ValidationException::withMessages(['account' => 'Reassign active activities before changing this coordinator.']);
            }
            $this->saveDemo('profiles', 'user_id', array_merge($p, $d), $s);
            if ($d['role'] === 'senior') {
                $this->saveDemo('senior_profiles', 'user_id', ['user_id' => $id, 'verification_status' => $d['verification_status']], $s);
            }
        } else {
            $s->rpc('manage_user', ['target' => $id, 'payload' => $d, 'reason' => $d['reason']]);
        }

        return back()->with('status', 'Account updated.');
    }

    public function category(Request $r, Community $s)
    {
        $this->admin($s);
        $d = $r->validate(['name' => 'required|string|max:80', 'description' => 'nullable|string|max:1000', 'is_active' => 'required|boolean', 'category_id' => 'nullable|string']);
        if ($s->demo()) {
            $this->saveDemo('categories', 'category_id', array_merge($d, ['category_id' => $d['category_id'] ?? (string) Str::uuid(), 'is_active' => (bool) $d['is_active']]), $s);
        } else {
            $s->rpc('save_category', ['payload' => $d, 'target' => $d['category_id'] ?? null]);
        }

        return back()->with('status', 'Category saved.');
    }

    public function reports(Community $s)
    {
        $this->staff($s);
        $rows = [];
        foreach ($this->activities($s) as $a) {
            $p = collect($this->participants($a['activity_id'], $s));
            $rows[] = ['title' => $a['title'], 'category' => $a['categories']['name'] ?? 'Uncategorized', 'coordinator' => $a['coordinator_name'] ?? $a['coordinator_id'], 'confirmed' => $p->whereIn('status', ['confirmed', 'completed'])->count(), 'waitlisted' => $p->where('status', 'waitlisted')->count(), 'attended' => $p->whereStrict('attended', true)->count(), 'absent' => $p->whereStrict('attended', false)->count(), 'unrecorded' => $p->whereIn('status', ['confirmed', 'completed'])->whereNull('attended')->count()];
        }

        return view('reports', ['rows' => $rows, 'demo' => $s->demo()]);
    }

    public function register(Request $r,Community $s)
    {
        $d = $r->validate(['full_name' => 'required|string|max:120', 'email' => 'required|email|max:254', 'password' => 'required|string|min:12|max:128|confirmed']);
        abort_if($s->demo(),422,'Registration is available only when Supabase is configured.');
        $s->api('POST','/auth/v1/signup',['email' => $d['email'], 'password' => $d['password'], 'data' => ['full_name' => $d['full_name']]]);

        return redirect('/login')->with('status','Registration submitted. Check your email for any required confirmation, then sign in.');
    }
}
