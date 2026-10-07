@extends('layout')
@section('title','Participation reports')
@section('content')

@php
    $query = collect($filters)->filter(fn ($value) => $value !== null && $value !== '')->all();
    $exportQuery = http_build_query($query);
@endphp

<div class="section-heading align-items-end">
    <div>
        <p class="eyebrow mb-1">REPORTS</p>
        <h1 class="mb-1">Participation reporting</h1>
        <p class="intro mb-0">Filter activity performance, review participation trends, and export the current admin view.</p>
    </div>
    @if($role === 'admin')
        <a class="btn btn-outline-primary" href="/reports/export{{ $exportQuery ? '?'.$exportQuery : '' }}">
            Export CSV
        </a>
    @endif
</div>

<form method="get" action="/reports" class="panel report-filter-panel">
    <div class="report-filter-grid">
        <div>
            <label for="report-from">From</label>
            <input class="form-control" type="date" id="report-from" name="from" value="{{ $filters['from'] ?? '' }}">
        </div>
        <div>
            <label for="report-to">To</label>
            <input class="form-control" type="date" id="report-to" name="to" value="{{ $filters['to'] ?? '' }}">
        </div>
        <div>
            <label for="report-category">Category</label>
            <select class="form-select" id="report-category" name="category">
                <option value="">All categories</option>
                @foreach($categoryOptions as $category)
                    <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ $category }}</option>
                @endforeach
            </select>
        </div>
        @if($role === 'admin')
            <div>
                <label for="report-coordinator">Coordinator</label>
                <select class="form-select" id="report-coordinator" name="coordinator">
                    <option value="">All coordinators</option>
                    @foreach($coordinatorOptions as $id => $name)
                        <option value="{{ $id }}" @selected(($filters['coordinator'] ?? '') === $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div>
            <label for="report-status">Activity status</label>
            <select class="form-select" id="report-status" name="status">
                <option value="">All statuses</option>
                @foreach(['draft','open','full','ongoing','completed','cancelled','archived'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="report-filter-actions">
            <button class="btn btn-primary" type="submit">Apply filters</button>
            <a class="btn btn-outline-secondary" href="/reports">Clear</a>
        </div>
    </div>
</form>

<div class="metric-grid report-metrics">
    <div><strong>{{ $stats['activities'] }}</strong><span>Activities</span></div>
    <div><strong>{{ $stats['registered'] }}</strong><span>Registered</span></div>
    <div><strong>{{ $stats['attendance_rate'] }}%</strong><span>Attendance rate</span></div>
    <div><strong>{{ $stats['waitlisted'] }}</strong><span>Waitlisted</span></div>
    <div><strong>{{ $stats['paid'] }}</strong><span>Cash payments recorded</span></div>
    <div><strong>{{ $stats['unpaid'] }}</strong><span>Payments due</span></div>
    @if($role === 'admin')
        <div><strong>₱{{ number_format($stats['cash_collected'],2) }}</strong><span>Recorded cash collected</span></div>
    @endif
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-7">
        <section class="panel h-100 m-0 report-chart-panel">
            <div class="section-heading mb-2">
                <div>
                    <h2 class="fs-5 mb-1">Participation trend</h2>
                    <p class="text-muted small mb-0">Registered participants compared with recorded attendance by activity month.</p>
                </div>
            </div>
            <div class="report-chart-wrap">
                <canvas
                    class="report-chart"
                    width="900"
                    height="360"
                    data-report-chart
                    data-chart-type="line"
                    data-chart='@json($charts["trend"])'
                    aria-label="Participation trend chart"
                    role="img"></canvas>
            </div>
            @if(empty($charts['trend']['labels']))
                <p class="text-muted mb-0">No trend data matches the current filters.</p>
            @endif
        </section>
    </div>
    <div class="col-xl-5">
        <section class="panel h-100 m-0 report-chart-panel">
            <div class="section-heading mb-2">
                <div>
                    <h2 class="fs-5 mb-1">Category demand</h2>
                    <p class="text-muted small mb-0">Registered and waitlisted participants by category.</p>
                </div>
            </div>
            <div class="report-chart-wrap">
                <canvas
                    class="report-chart"
                    width="700"
                    height="360"
                    data-report-chart
                    data-chart-type="bar"
                    data-chart='@json($charts["category"])'
                    aria-label="Category demand chart"
                    role="img"></canvas>
            </div>
            @if(empty($charts['category']['labels']))
                <p class="text-muted mb-0">No category data matches the current filters.</p>
            @endif
        </section>
    </div>
</div>

<div class="panel table-responsive">
    <div class="section-heading mb-2">
        <div>
            <h2 class="fs-5 mb-1">Activity breakdown</h2>
            <p class="text-muted small mb-0">The export uses these same filtered rows.</p>
        </div>
    </div>

    <table class="table align-middle">
        <thead>
            <tr>
                <th>Activity</th>
                <th>Start</th>
                <th>Status</th>
                <th>Registered</th>
                <th>Waitlist</th>
                <th>Attendance</th>
                <th>Payment</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>
                        <strong>{{ $row['title'] }}</strong>
                        <small class="d-block text-muted">{{ $row['category'] }} · {{ $row['coordinator'] }}</small>
                    </td>
                    <td>{{ $row['start_label'] }}</td>
                    <td>{{ ucfirst($row['status']) }}</td>
                    <td>{{ $row['confirmed'] }} / {{ $row['capacity'] }}</td>
                    <td>{{ $row['waitlisted'] }}</td>
                    <td>
                        {{ $row['attended'] }} attended
                        <small class="d-block text-muted">
                            {{ $row['absent'] }} absent · {{ $row['unrecorded'] }} not recorded · {{ $row['attendance_rate'] }}%
                        </small>
                    </td>
                    <td>
                        @if($row['is_free'])
                            Free
                        @else
                            {{ $row['paid'] }} paid · {{ $row['unpaid'] }} due
                            <small class="d-block text-muted">
                                ₱{{ number_format($row['fee'],2) }} each
                                @if($role === 'admin')
                                    · ₱{{ number_format($row['cash_collected'],2) }} recorded
                                @endif
                            </small>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">No activity data matches the current filters.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <section class="panel h-100 m-0">
            <h2 class="fs-5">Category summary</h2>
            @forelse(collect($rows)->groupBy('category')->sortKeys() as $category => $group)
                <div class="report-line">
                    <span>{{ $category }}</span>
                    <strong>{{ $group->sum('confirmed') }} registered</strong>
                    <small>{{ $group->sum('waitlisted') }} waitlisted · {{ $group->sum('attended') }} attended</small>
                </div>
            @empty
                <p class="text-muted">No category data yet.</p>
            @endforelse
        </section>
    </div>
    <div class="col-lg-6">
        <section class="panel h-100 m-0">
            <h2 class="fs-5">Coordinator workload</h2>
            @forelse(collect($rows)->groupBy('coordinator')->sortKeys() as $coordinator => $group)
                <div class="report-line">
                    <span>{{ $coordinator }}</span>
                    <strong>{{ $group->count() }} activities</strong>
                    <small>{{ $group->sum('confirmed') }} registered · {{ $group->sum('waitlisted') }} waitlisted</small>
                </div>
            @empty
                <p class="text-muted">No coordinator data yet.</p>
            @endforelse
        </section>
    </div>
</div>

@endsection
