@extends('layout')
@section('title','Administration')
@section('content')

@php
    $userCollection = collect($users);
    $pendingVerification = $userCollection
        ->where('role','senior')
        ->filter(fn ($u) => ($seniors[$u['user_id']]['verification_status'] ?? 'pending') === 'pending')
        ->count();
@endphp

<div class="section-heading align-items-end">
    <div>
        <p class="eyebrow mb-1">ADMINISTRATION</p>
        <h1 class="mb-1">Community administration</h1>
        <p class="intro mb-0">Manage accounts, senior verification, system settings, categories, and privileged activity records.</p>
    </div>
</div>

<div class="metric-grid admin-metrics">
    <div><strong>{{ $userCollection->where('role','senior')->where('account_status','active')->count() }}</strong><span>Active seniors</span></div>
    <div><strong>{{ $userCollection->where('role','coordinator')->where('account_status','active')->count() }}</strong><span>Active coordinators</span></div>
    <div><strong>{{ $pendingVerification }}</strong><span>Pending verification</span></div>
    <div><strong>{{ collect($categories)->where('is_active',true)->count() }}</strong><span>Active categories</span></div>
</div>


<section class="panel">
    <div class="section-heading mb-3">
        <div>
            <p class="eyebrow mb-1">SYSTEM SETTINGS</p>
            <h2 class="fs-5 mb-1">Senior verification policy</h2>
            <p class="text-muted mb-0">Control whether a senior account must be verified before it can enroll in an activity or receive a promoted waitlist seat.</p>
        </div>
        <span class="badge {{ ($settings['require_verification'] ?? false) ? 'text-bg-success' : 'text-bg-secondary' }}">
            {{ ($settings['require_verification'] ?? false) ? 'Verification required' : 'Verification optional' }}
        </span>
    </div>

    <div class="alert alert-warning mb-3" role="note">
        <strong>Important:</strong> Turning this on does not remove existing enrollments. It applies to new enrollment attempts and waitlist promotions. Seniors can be verified in the Users and verification section below.
    </div>

    <form class="form-grid" method="post" action="/administration/settings">
        @csrf
        <div>
            <label for="require-verification">Enrollment verification requirement</label>
            <select class="form-select" name="require_verification" id="require-verification" required>
                <option value="0" @selected(!($settings['require_verification'] ?? false))>Verification optional</option>
                <option value="1" @selected($settings['require_verification'] ?? false)>Require verified senior account</option>
            </select>
        </div>
        <div>
            <label for="settings-reason">Reason for change</label>
            <input class="form-control" name="reason" id="settings-reason" required minlength="3" maxlength="500" placeholder="Required for the audit log">
        </div>
        <div class="wide d-flex justify-content-between align-items-center flex-wrap gap-2">
            <small class="text-muted">Only administrators can change this policy. Every change is recorded in the audit log.</small>
            <button class="btn btn-primary" type="submit">Save system settings</button>
        </div>
    </form>
</section>

<section class="panel">
    <div class="section-heading mb-3">
        <div>
            <h2 class="fs-5 mb-1">Users and verification</h2>
            <p class="text-muted mb-0">Search a person, then expand their account to make an authorized change.</p>
        </div>
    </div>

    <label for="admin-user-search">Find a user</label>
    <input id="admin-user-search" type="search" class="form-control mb-3" placeholder="Search by name or role" data-admin-user-search>

    <div data-admin-user-list>
        @foreach($users as $u)
            <details class="admin-row" data-admin-user="{{ strtolower(($u['full_name'] ?? '').' '.($u['role'] ?? '')) }}">
                <summary>
                    <span>{{ $u['full_name'] }}</span>
                    <span class="admin-summary-meta">
                        {{ ucfirst($u['role']) }} · {{ ucfirst($u['account_status']) }}
                        @if($u['role']==='senior')
                            · {{ ucfirst($seniors[$u['user_id']]['verification_status'] ?? 'pending') }}
                        @endif
                    </span>
                </summary>

                <form class="form-grid mt-3" method="post" action="/administration/users/{{ $u['user_id'] }}">
                    @csrf
                    <div>
                        <label for="role-{{ $u['user_id'] }}">Role</label>
                        <select class="form-select" name="role" id="role-{{ $u['user_id'] }}">
                            @foreach(['senior','coordinator','admin'] as $role)
                                <option value="{{ $role }}" @selected($role===$u['role'])>{{ ucfirst($role) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="status-{{ $u['user_id'] }}">Account status</label>
                        <select class="form-select" name="account_status" id="status-{{ $u['user_id'] }}">
                            @foreach(['active','disabled'] as $status)
                                <option value="{{ $status }}" @selected($status===$u['account_status'])>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="verify-{{ $u['user_id'] }}">Senior verification</label>
                        <select class="form-select" name="verification_status" id="verify-{{ $u['user_id'] }}">
                            @foreach(['pending','verified','rejected'] as $status)
                                <option value="{{ $status }}" @selected($status===($seniors[$u['user_id']]['verification_status']??'pending'))>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="reason-{{ $u['user_id'] }}">Reason for change</label>
                        <input class="form-control" name="reason" id="reason-{{ $u['user_id'] }}" required minlength="3" maxlength="500" placeholder="Required for audit history">
                    </div>
                    <div class="wide d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <small class="text-muted">Account ID: {{ $u['user_id'] }}</small>
                        <button class="btn btn-primary">Save account changes</button>
                    </div>
                </form>
            </details>
        @endforeach
    </div>
    <p class="text-muted mb-0" data-admin-user-empty hidden>No users match that search.</p>
</section>

<section class="panel">
    <h2 class="fs-5">Activity categories</h2>
    <p class="text-muted">Keep categories short and easy for seniors to understand.</p>
    @foreach(array_merge($categories,[['category_id'=>'','name'=>'','description'=>'','is_active'=>true]]) as $c)
        <details class="admin-row">
            <summary>{{ trim($c['name'] ?? '') ?: 'Add a category' }} {{ ($c['is_active'] ?? true) ? '' : '(inactive)' }}</summary>
            <form class="form-grid mt-3" method="post" action="/administration/categories">
                @csrf
                <input type="hidden" name="category_id" value="{{ $c['category_id'] }}">
                <div>
                    <label for="name-{{ $c['category_id'] }}">Name</label>
                    <input class="form-control" name="name" id="name-{{ $c['category_id'] }}" required maxlength="80" value="{{ trim($c['name'] ?? '') }}">
                </div>
                <div>
                    <label for="active-{{ $c['category_id'] }}">Status</label>
                    <select class="form-select" name="is_active" id="active-{{ $c['category_id'] }}">
                        <option value="1">Active</option>
                        <option value="0" @selected(!($c['is_active'] ?? true))>Inactive</option>
                    </select>
                </div>
                <div class="wide">
                    <label for="description-{{ $c['category_id'] }}">Description</label>
                    <textarea class="form-control" name="description" id="description-{{ $c['category_id'] }}" maxlength="1000">{{ $c['description'] ?? '' }}</textarea>
                </div>
                <div><button class="btn btn-primary">Save category</button></div>
            </form>
        </details>
    @endforeach
</section>

<section class="panel">
    <h2 class="fs-5">Latest audit events</h2>
    <p class="text-muted">The most recent privileged changes, newest first.</p>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Time</th><th>Actor</th><th>Action</th><th>Target</th><th>Details</th></tr></thead>
            <tbody>
                @forelse($logs as $log)
                    @php
                        $actor = collect($users)->firstWhere('user_id',$log['actor_id'])['full_name'] ?? 'System / removed user';
                        $action = ucwords(str_replace(['.','_'], ' ', $log['action_type']));
                    @endphp
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($log['created_at'])->timezone(config('app.timezone'))->format('M j, g:i A') }}</td>
                        <td>{{ $actor }}</td>
                        <td>{{ $action }}</td>
                        <td>{{ ucfirst($log['target_type']) }}</td>
                        <td><details><summary>View details</summary><pre class="audit-details">{{ json_encode($log['details'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre></details></td>
                    </tr>
                @empty
                    <tr><td colspan="5">No audit events yet. Privileged changes will appear here.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@endsection
