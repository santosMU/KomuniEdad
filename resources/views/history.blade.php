@extends('layout')
@section('title','My participation')
@section('content')

<div class="section-heading align-items-end">
    <div>
        <p class="eyebrow mb-1">MY PARTICIPATION</p>
        <h1 class="mb-1">Activity history</h1>
        <p class="intro mb-0">Review activities you joined, attendance records, and feedback.</p>
    </div>
    <a href="/" class="btn btn-outline-primary">Discover activities</a>
</div>

<div class="history-list">
    @forelse($enrollments as $e)
        @php
            $a = $activities[$e['activity_id']] ?? null;
            $present = $attendance[$e['enrollment_id']]['attended'] ?? $e['attended'] ?? null;
            $statusLabel = match($e['status']) {
                'confirmed' => 'Registered',
                'completed' => 'Completed',
                'waitlisted' => 'Waitlisted',
                'cancelled' => 'Cancelled',
                default => ucfirst($e['status'])
            };
        @endphp

        <article class="panel history-card">
            <div class="history-card-head">
                <div>
                    <span class="badge bg-secondary-subtle text-secondary mb-2">{{ $statusLabel }}</span>
                    <h2 class="fs-4 mb-1">{{ $a['title'] ?? 'Past activity' }}</h2>
                    @if($a)
                        <p class="text-muted mb-1">
                            {{ \Carbon\Carbon::parse($a['start_at'])->timezone(config('app.timezone'))->format('M j, Y · g:i A') }}
                            · {{ $a['venue'] }}
                        </p>
                    @endif
                </div>

                <div class="history-attendance">
                    @if($present === null)
                        <span class="badge bg-light text-secondary">Attendance not recorded</span>
                    @elseif($present)
                        <span class="badge bg-success-subtle text-success">Attended</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary">Absent</span>
                    @endif
                </div>
            </div>

            @if(isset($feedback[$e['enrollment_id']]))
                <div class="history-feedback">
                    <strong>Your feedback: {{ $feedback[$e['enrollment_id']]['rating'] }} / 5</strong>
                    @if(!empty($feedback[$e['enrollment_id']]['comments']))
                        <p class="mb-0 mt-1 text-muted">{{ $feedback[$e['enrollment_id']]['comments'] }}</p>
                    @endif
                </div>
            @elseif($e['status'] === 'completed' && $present)
                <details class="mt-3">
                    <summary>Share feedback</summary>
                    <form method="post" action="/history/{{ $e['enrollment_id'] }}/feedback" class="form-grid mt-3">
                        @csrf
                        <div>
                            <label for="rating-{{ $e['enrollment_id'] }}">How was your experience?</label>
                            <select class="form-select" name="rating" id="rating-{{ $e['enrollment_id'] }}">
                                @foreach([5,4,3,2,1] as $score)
                                    <option value="{{ $score }}">{{ $score }} / 5</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="wide">
                            <label for="comments-{{ $e['enrollment_id'] }}">Comments (optional)</label>
                            <textarea class="form-control" name="comments" id="comments-{{ $e['enrollment_id'] }}" maxlength="2000" rows="3"></textarea>
                        </div>
                        <div class="wide"><button class="btn btn-primary">Send feedback</button></div>
                    </form>
                </details>
            @endif

            @if($a)
                <a class="btn btn-outline-secondary btn-sm mt-3" href="/activities/{{ $a['activity_id'] }}">View activity details</a>
            @endif
        </article>
    @empty
        <div class="empty-state">
            <h3>No participation history yet.</h3>
            <p>Activities you join will appear here.</p>
            <a class="btn btn-primary" href="/">Explore activities</a>
        </div>
    @endforelse
</div>

@endsection
