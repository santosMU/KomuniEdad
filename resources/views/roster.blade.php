@extends('layout')
@section('title','Participants')
@section('content')

<a class="back-link" href="/workspace">← Back to workspace</a>

<div class="section-heading align-items-end">
    <div>
        <p class="eyebrow mb-1">PARTICIPANTS</p>
        <h1 class="mb-1">{{ $activity['title'] }}</h1>
        <p class="intro mb-0">Manage registrations, onsite cash payments, and attendance.</p>
    </div>
    <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($activity['status']) }}</span>
</div>

@if($demo)
    <p class="alert alert-info">Demo roster shows enrollments created in this browser session.</p>
@endif

<div class="metric-grid roster-metrics">
    <div>
        <strong>{{ collect($participants)->whereIn('status',['confirmed','completed'])->count() }}</strong>
        <span>Registered</span>
    </div>
    <div>
        <strong>{{ collect($participants)->where('status','waitlisted')->count() }}</strong>
        <span>Waitlisted</span>
    </div>
    <div>
        <strong>{{ collect($participants)->whereStrict('attended',true)->count() }}</strong>
        <span>Attended</span>
    </div>
</div>

@if($activity['is_free'] ?? true)
    <div class="alert alert-secondary"><strong>Free activity.</strong> No payment record is required.</div>
@else
    <div class="alert alert-info">
        <strong>Onsite cash payment:</strong>
        ₱{{ number_format((float)($activity['fee'] ?? 0),2) }} per confirmed participant.
        Payment must be recorded before attendance can be marked.
    </div>
@endif

<div class="panel">
    <div class="row g-3 align-items-end">
        <div class="col-md-7">
            <label for="participant-search">Find a participant</label>
            <input id="participant-search" class="form-control" type="search" placeholder="Search by participant name" data-participant-search>
        </div>
        <div class="col-md-5 text-md-end">
            <details class="staff-advanced-action">
                <summary>Add or manage a participant</summary>
                <div class="mt-3 text-start">
                    <p class="text-muted small">For authorized walk-ins or manual corrections. Use the senior account ID supplied by an administrator.</p>
                    <form method="post" class="form-grid" action="/workspace/{{ $activity['activity_id'] }}/enrollment">
                        @csrf
                        <div>
                            <label for="senior_id">Senior account ID</label>
                            <input class="form-control" name="senior_id" id="senior_id" required placeholder="{{ $demo ? 'demo-senior' : 'Account UUID' }}">
                        </div>
                        <div>
                            <label for="enrollment-status">Action</label>
                            <select class="form-select" name="status" id="enrollment-status">
                                <option value="confirmed">Register / confirm</option>
                                <option value="waitlisted">Move to waitlist</option>
                                <option value="cancelled">Cancel registration</option>
                            </select>
                        </div>
                        <div class="wide">
                            <label for="enrollment-reason">Reason</label>
                            <input class="form-control" name="reason" id="enrollment-reason" required minlength="3" maxlength="500" placeholder="Example: Authorized walk-in">
                        </div>
                        <div class="wide"><button class="btn btn-primary">Save participant change</button></div>
                    </form>
                </div>
            </details>
        </div>
    </div>
</div>

<div class="panel">
    <div class="participant-list" data-participant-list>
        @forelse($participants as $p)
            @php
                $paymentPaid = ($p['payment_status'] ?? 'unpaid') === 'paid';
                $activeParticipant = in_array($p['status'], ['confirmed','completed'], true);
                $attendanceAvailable = $activeParticipant && in_array($activity['status'], ['ongoing','completed'], true);
                $attendanceBlockedByPayment = !($activity['is_free'] ?? true) && !$paymentPaid;
            @endphp

            <article class="participant-card" data-participant-name="{{ strtolower($p['full_name']) }}">
                <div class="participant-card-main">
                    <div>
                        <h2 class="fs-5 mb-1">{{ $p['full_name'] }}</h2>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="badge bg-secondary-subtle text-secondary">
                                {{ match($p['status']) {
                                    'confirmed' => 'Registered',
                                    'completed' => 'Completed',
                                    'waitlisted' => 'Waitlisted',
                                    'cancelled' => 'Cancelled',
                                    default => ucfirst($p['status'])
                                } }}
                            </span>

                            @if(!($activity['is_free'] ?? true))
                                <span class="badge {{ $paymentPaid ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-dark' }}">
                                    {{ $paymentPaid ? 'Cash paid' : 'Payment due' }}
                                </span>
                            @endif

                            <span class="badge {{ $p['attended'] === true ? 'bg-success-subtle text-success' : 'bg-light text-secondary' }}">
                                {{ $p['attended'] === null ? 'Attendance not recorded' : ($p['attended'] ? 'Attended' : 'Absent') }}
                            </span>
                        </div>
                    </div>
                    <small class="text-muted">Account ID: {{ $p['senior_id'] }}</small>
                </div>

                <div class="participant-actions">
                    @if(!($activity['is_free'] ?? true) && $activeParticipant)
                        <details>
                            <summary>{{ $paymentPaid ? 'Correct payment record' : 'Record onsite payment' }}</summary>
                            <form method="post" action="/workspace/{{ $activity['activity_id'] }}/payment" class="form-grid mt-3">
                                @csrf
                                <input type="hidden" name="enrollment_id" value="{{ $p['enrollment_id'] }}">
                                <div>
                                    <label for="cash-status-{{ $p['enrollment_id'] }}">Payment status</label>
                                    <select class="form-select" name="paid" id="cash-status-{{ $p['enrollment_id'] }}">
                                        <option value="1" @selected($paymentPaid)>Paid in cash</option>
                                        <option value="0" @selected(!$paymentPaid)>Unpaid</option>
                                    </select>
                                </div>
                                <div>
                                    <label>Amount</label>
                                    <input class="form-control" value="₱{{ number_format((float)($activity['fee'] ?? 0),2) }}" disabled>
                                </div>
                                <div class="wide">
                                    <label for="cash-reason-{{ $p['enrollment_id'] }}">Staff note / reason</label>
                                    <input class="form-control" id="cash-reason-{{ $p['enrollment_id'] }}" name="reason" required minlength="3" maxlength="500" placeholder="Example: Cash received at front desk">
                                </div>
                                <div class="wide"><button class="btn btn-outline-primary">Save payment record</button></div>
                            </form>
                        </details>
                    @endif

                    @if($attendanceAvailable)
                        @if($attendanceBlockedByPayment)
                            <div class="alert alert-warning mb-0 py-2">Record onsite payment before attendance.</div>
                        @else
                            <details>
                                <summary>{{ $p['attended'] === null ? 'Record attendance' : 'Update attendance' }}</summary>
                                <form method="post" action="/workspace/{{ $activity['activity_id'] }}/attendance" class="form-grid mt-3">
                                    @csrf
                                    <input type="hidden" name="enrollment_id" value="{{ $p['enrollment_id'] }}">
                                    <div>
                                        <label for="attendance-{{ $p['enrollment_id'] }}">Attendance</label>
                                        <select class="form-select" name="attended" id="attendance-{{ $p['enrollment_id'] }}">
                                            <option value="1" @selected($p['attended'] === true)>Attended</option>
                                            <option value="0" @selected($p['attended'] === false)>Absent</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="remarks-{{ $p['enrollment_id'] }}">Remarks</label>
                                        <input class="form-control" id="remarks-{{ $p['enrollment_id'] }}" name="remarks" maxlength="1000" placeholder="Optional remarks" value="{{ $p['remarks'] }}">
                                    </div>
                                    <div class="wide"><button class="btn btn-primary">Save attendance</button></div>
                                </form>
                            </details>
                        @endif
                    @endif
                </div>
            </article>
        @empty
            <div class="empty-state">
                <h3>No participants yet.</h3>
                <p>Registrations for this activity will appear here.</p>
            </div>
        @endforelse
    </div>

    <p class="text-muted mb-0 mt-3" data-participant-empty hidden>No participants match that search.</p>
</div>

@endsection
