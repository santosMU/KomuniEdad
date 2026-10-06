@extends('layout')
@section('title','Participation reports')
@section('content')

@php
    $collection = collect($rows);
    $registered = $collection->sum('confirmed');
    $attended = $collection->sum('attended');
    $attendanceRate = $registered > 0 ? round(($attended / $registered) * 100) : 0;
    $paid = $collection->sum('paid');
    $unpaid = $collection->sum('unpaid');
@endphp

<div class="section-heading align-items-end">
    <div>
        <p class="eyebrow mb-1">REPORTS</p>
        <h1 class="mb-1">Participation at a glance</h1>
        <p class="intro mb-0">A practical summary of registrations, attendance, waitlists, and onsite cash records.</p>
    </div>
</div>

<div class="metric-grid report-metrics">
    <div><strong>{{ $collection->count() }}</strong><span>Activities</span></div>
    <div><strong>{{ $registered }}</strong><span>Registered</span></div>
    <div><strong>{{ $attendanceRate }}%</strong><span>Attendance rate</span></div>
    <div><strong>{{ $collection->sum('waitlisted') }}</strong><span>Waitlisted</span></div>
    @if($paid + $unpaid > 0)
        <div><strong>{{ $paid }}</strong><span>Cash payments recorded</span></div>
        <div><strong>{{ $unpaid }}</strong><span>Payments still due</span></div>
    @endif
</div>

<div class="panel table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Activity</th>
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
                    <td>{{ ucfirst($row['status']) }}</td>
                    <td>{{ $row['confirmed'] }} / {{ $row['capacity'] }}</td>
                    <td>{{ $row['waitlisted'] }}</td>
                    <td>
                        {{ $row['attended'] }} attended
                        <small class="d-block text-muted">{{ $row['attendance_rate'] }}% of registered</small>
                    </td>
                    <td>
                        @if($row['is_free'])
                            Free
                        @else
                            {{ $row['paid'] }} paid · {{ $row['unpaid'] }} due
                            <small class="d-block text-muted">₱{{ number_format($row['fee'],2) }} each</small>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">No activity data yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <section class="panel h-100 m-0">
            <h2 class="fs-5">Category demand</h2>
            @forelse($collection->groupBy('category')->sortKeys() as $category => $group)
                <div class="report-line">
                    <span>{{ $category }}</span>
                    <strong>{{ $group->sum('confirmed') }} registered</strong>
                    <small>{{ $group->sum('waitlisted') }} waitlisted</small>
                </div>
            @empty
                <p class="text-muted">No category data yet.</p>
            @endforelse
        </section>
    </div>
    <div class="col-lg-6">
        <section class="panel h-100 m-0">
            <h2 class="fs-5">Coordinator workload</h2>
            @forelse($collection->groupBy('coordinator')->sortKeys() as $coordinator => $group)
                <div class="report-line">
                    <span>{{ $coordinator }}</span>
                    <strong>{{ $group->count() }} activities</strong>
                    <small>{{ $group->sum('confirmed') }} registered participants</small>
                </div>
            @empty
                <p class="text-muted">No coordinator data yet.</p>
            @endforelse
        </section>
    </div>
</div>

@endsection
