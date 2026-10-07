@extends('layout')
@section('title', 'Program Workspace')
@section('content')

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Program workspace</h1>
        <p class="text-muted mb-0">Manage community activities, monitor registrations, and track participant status.</p>
    </div>
    <div class="d-flex align-items-center gap-3">
        <!-- View Toggle (Ideas 1-3) -->
        <div class="btn-group shadow-sm" role="group" aria-label="Workspace view toggle">
            <button type="button" class="btn btn-outline-success btn-sm px-3 active text-nowrap" id="btn-table" data-workspace-view="table">
                <i class="bi bi-table me-1"></i> Table
            </button>
            <button type="button" class="btn btn-outline-success btn-sm px-3 text-nowrap" id="btn-grid" data-workspace-view="grid">
                <i class="bi bi-grid-3x3-gap me-1"></i> Card Grid (Senior View)
            </button>
        </div>
        <a href="/workspace/create" class="btn btn-success fw-semibold d-inline-flex align-items-center gap-2 px-3 py-2 shadow-sm text-nowrap">
            <i class="bi bi-plus-lg"></i> Create activity
        </a>
    </div>
</div>

<!-- Quick Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="panel p-3 border-0 shadow-sm bg-white rounded-3">
            <div class="text-muted small fw-semibold uppercase">Assigned activities</div>
            <div class="fs-3 fw-bold text-dark mt-1">{{ count($activities) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="panel p-3 border-0 shadow-sm bg-white rounded-3">
            <div class="text-muted small fw-semibold uppercase">Open for registration</div>
            <div class="fs-3 fw-bold text-dark mt-1">{{ collect($activities)->where('status', 'open')->count() }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="panel p-3 border-0 shadow-sm bg-white rounded-3">
            <div class="text-muted small fw-semibold uppercase">Allocated slots</div>
            <div class="fs-3 fw-bold text-dark mt-1">{{ collect($activities)->sum('confirmed') }}</div>
        </div>
    </div>
</div>

<!-- TABLE VIEW -->
<div id="view-table-container" class="panel p-0 shadow-sm border-0 rounded-3 overflow-hidden bg-white mb-5">
    <div class="p-3 border-bottom bg-light fw-bold text-dark">Programs and activities</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase text-muted">
                <tr>
                    <th class="py-3 px-3">Activity</th>
                    <th class="py-3">Schedule</th>
                    <th class="py-3">Status</th>
                    <th class="py-3">Slots</th>
                    <th class="py-3 text-end px-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $a)
                @php
                    $img = $a['image_url'] ?? $a['image'] ?? null;
                    $src = $img
                        ? (str_starts_with($img, 'data:') ? $img : (filter_var($img, FILTER_VALIDATE_URL) ? $img : asset('storage/' . $img)))
                        : 'https://images.pexels.com/photos/19524029/pexels-photo-19524029.jpeg?auto=compress&cs=tinysrgb&w=150';
                @endphp
                <tr>
                    <td class="py-3 px-3">
                        <div class="d-flex align-items-center gap-3">
                            <img src="{{ $src }}" alt="" class="rounded object-fit-cover shadow-sm" style="width: 48px; height: 48px; min-width: 48px;">
                            <div>
                                <div class="fw-bold text-dark">{{ $a['title'] }}</div>
                                <div class="text-muted small mb-1">{{ $a['venue'] }}</div>
                                <span class="badge bg-secondary bg-opacity-75 text-white" style="font-size: 0.7rem;">{{ trim($a['categories']['name'] ?? 'Community') }}</span>
                                <span class="badge {{ ($a['is_free'] ?? true) ? 'bg-light text-secondary' : 'bg-warning-subtle text-dark' }}" style="font-size: 0.7rem;">
                                    {{ ($a['is_free'] ?? true) ? 'Free' : '₱'.number_format((float)($a['fee'] ?? 0),2).' cash' }}
                                </span>
                            </div>
                        </div>
                    </td>
                    <td class="py-3 small text-secondary">
                        {{ \Carbon\Carbon::parse($a['start_at'])->format('M j, Y • g:i A') }}
                    </td>
                    <td class="py-3">
                        <span class="badge {{ $a['status'] === 'open' ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary' }} px-2 py-1 fw-semibold text-uppercase" style="font-size: 0.75rem;">
                            ● {{ ucfirst($a['status']) }}
                        </span>
                    </td>
                    <td class="py-3 fw-semibold small text-dark">
                        {{ $a['confirmed'] ?? 0 }} / {{ $a['capacity'] }}
                    </td>
                    <td class="py-3 text-end px-3">
                        <div class="d-inline-flex gap-2">
                            <a href="/workspace/{{ $a['activity_id'] }}/edit" class="btn btn-outline-secondary btn-sm px-3 fw-semibold">Edit</a>
                            <a href="/workspace/{{ $a['activity_id'] }}/participants" class="btn btn-outline-primary btn-sm px-3 fw-semibold">Participants ({{ (int)($a['confirmed'] ?? 0) + (int)($a['waitlisted'] ?? 0) }})</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">No activities found in your workspace. Click "Create activity" to start one.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- CARD GRID VIEW -->
<div id="view-grid-container" class="row g-4 mb-5" style="display: none;">
    @forelse($activities as $a)
    @php
        $img = $a['image_url'] ?? $a['image'] ?? null;
        $src = $img
            ? (str_starts_with($img, 'data:') ? $img : (filter_var($img, FILTER_VALIDATE_URL) ? $img : asset('storage/' . $img)))
            : 'https://images.pexels.com/photos/19524029/pexels-photo-19524029.jpeg?auto=compress&cs=tinysrgb&w=600';
    @endphp
    <div class="col-md-6 col-xl-4">
        <div class="card shadow-sm border-0 rounded-3 overflow-hidden bg-light h-100 d-flex flex-column clickable-card" data-card-href="/workspace/{{ $a['activity_id'] }}/edit" tabindex="0" role="link" aria-label="Edit {{ $a['title'] }}">
            <img src="{{ $src }}" class="card-img-top object-fit-cover" style="height: 150px;" alt="">
            <div class="p-3 d-flex flex-column flex-grow-1">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="badge bg-secondary" style="font-size: 0.75rem;">{{ trim($a['categories']['name'] ?? 'Community') }}</span>
                    <span class="badge {{ $a['status'] === 'open' ? 'bg-success' : 'bg-warning text-dark' }} text-uppercase" style="font-size: 0.7rem;">{{ ucfirst($a['status']) }}</span>
                </div>
                <h3 class="fs-6 fw-bold text-dark mb-1">{{ $a['title'] }}</h3>
                @if(!empty($a['tags']))
                    <div class="activity-tag-list mb-2">
                        @foreach($a['tags'] as $activityTag)
                            <span class="activity-tag">{{ ucfirst($activityTag) }}</span>
                        @endforeach
                    </div>
                @endif
                <p class="text-muted small mb-3 text-truncate">{{ $a['description'] }}</p>
                <div class="small text-secondary mb-3 mt-auto">
                    <div><strong>Schedule:</strong> {{ \Carbon\Carbon::parse($a['start_at'])->format('M j, Y • g:i A') }}</div>
                    <div><strong>Venue:</strong> {{ $a['venue'] }}</div>
                    <div><strong>Slots:</strong> {{ $a['confirmed'] ?? 0 }} / {{ $a['capacity'] }}</div>
                    <div><strong>Payment:</strong> {{ ($a['is_free'] ?? true) ? 'Free' : '₱'.number_format((float)($a['fee'] ?? 0),2).' cash onsite' }}</div>
                </div>
                <div class="d-flex gap-2 pt-2 border-top">
                    <a href="/workspace/{{ $a['activity_id'] }}/edit" class="btn btn-outline-secondary btn-sm flex-fill fw-semibold">Edit</a>
                    <a href="/workspace/{{ $a['activity_id'] }}/participants" class="btn btn-primary btn-sm flex-fill fw-semibold">Participants ({{ (int)($a['confirmed'] ?? 0) + (int)($a['waitlisted'] ?? 0) }})</a>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12 text-center py-5 text-muted">No activities found.</div>
    @endforelse
</div>

@endsection
