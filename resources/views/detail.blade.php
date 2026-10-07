@extends('layout')
@section('title', $activity['title'])
@section('content')

@php
    $portal = app(\App\Services\Community::class);
    $img = $activity['image'] ?? $activity['image_url'] ?? null;
    $src = $img
        ? (str_starts_with($img, 'data:') ? $img : (filter_var($img, FILTER_VALIDATE_URL) ? $img : asset('storage/' . $img)))
        : $portal->activityImage($activity);

    $status = strtolower((string) ($activity['status'] ?? ''));
    $registrationOpen = in_array($status, ['open', 'full'], true)
        && !empty($activity['cutoff_at'])
        && \Carbon\Carbon::parse($activity['cutoff_at'])->isFuture();

    $entry = collect($enrollments ?? [])->first(
        fn ($e) => $e['activity_id'] === $activity['activity_id'] && $e['status'] !== 'cancelled'
    );
    $coordinatorPhoto = $portal->profilePhotoUrl($activity['coordinator_avatar_path'] ?? null);
@endphp

<div class="container py-3" style="max-width: 800px;">
    <div class="mb-3">
        <a href="/" class="text-decoration-none fw-semibold text-secondary">
            <span aria-hidden="true">←</span> Back to activities
        </a>
    </div>

    <div class="card shadow-sm border-0 rounded-3 overflow-hidden bg-white mb-4">
        <img src="{{ $src }}" class="w-100 object-fit-cover" style="height: 300px;" alt="{{ $portal->activityImageAlt($activity) }}">

        <div class="p-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-secondary">{{ $activity['categories']['name'] ?? 'Community' }}</span>
                <span class="badge {{ $status === 'open' ? 'bg-success' : 'bg-warning text-dark' }}">
                    {{ ucfirst($status) }}
                </span>
            </div>

            @if(!empty($activity['tags']))
                <div class="activity-tag-list mb-2" aria-label="Activity interests">
                    @foreach($activity['tags'] as $activityTag)
                        <a class="activity-tag" href="/?tag={{ urlencode($activityTag) }}">{{ ucfirst($activityTag) }}</a>
                    @endforeach
                </div>
            @endif

            <h1 class="h3 fw-bold text-dark mb-2">{{ $activity['title'] }}</h1>
            <p class="text-muted mb-4">{{ $activity['description'] }}</p>

            <div class="row g-3 mb-4">
                <div class="col-sm-6">
                    <div class="small text-muted fw-semibold text-uppercase">When</div>
                    <div class="text-dark fw-medium">
                        {{ \Carbon\Carbon::parse($activity['start_at'])->timezone(config('app.timezone'))->format('l, F j · g:i A') }}
                        –
                        {{ \Carbon\Carbon::parse($activity['end_at'])->timezone(config('app.timezone'))->format('g:i A') }}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="small text-muted fw-semibold text-uppercase">Where</div>
                    <div class="text-dark fw-medium">{{ $activity['venue'] }}</div>
                </div>
                <div class="col-sm-6">
                    <div class="small text-muted fw-semibold text-uppercase">Registration closes</div>
                    <div class="text-dark fw-medium">{{ \Carbon\Carbon::parse($activity['cutoff_at'])->timezone(config('app.timezone'))->format('M j, g:i A') }}</div>
                </div>
                <div class="col-sm-6">
                    <div class="small text-muted fw-semibold text-uppercase">Payment</div>
                    <div class="text-dark fw-medium">
                        @if($activity['is_free'] ?? true)
                            Free activity
                        @else
                            ₱{{ number_format((float) ($activity['fee'] ?? 0), 2) }} cash, paid in person
                        @endif
                    </div>
                </div>
            </div>

            <div class="alert alert-light border border-success border-opacity-25 text-success mb-3">
                <strong>What to bring / Notes:</strong>
                {{ !empty($activity['requirements']) ? $activity['requirements'] : 'No special requirements.' }}
            </div>

            <div class="coordinator-card mb-4">
                <div class="coordinator-avatar" aria-hidden="true">
                    @if($coordinatorPhoto)
                        <img src="{{ $coordinatorPhoto }}" alt="">
                    @else
                        <span>{{ strtoupper(substr($activity['coordinator_name'] ?? 'C', 0, 1)) }}</span>
                    @endif
                </div>
                <div>
                    <div class="small text-muted fw-semibold text-uppercase">Your coordinator</div>
                    <strong>{{ $activity['coordinator_name'] ?? 'Community coordinator' }}</strong>
                    <p class="small text-muted mb-0">This coordinator manages registration and activity updates.</p>
                </div>
            </div>

            <div class="d-grid gap-2">
                @if($entry)
                    <div class="alert alert-success text-center mb-1 py-2">
                        Your enrollment: <strong>{{ ucfirst($entry['status']) }}</strong>
                        @if(!($activity['is_free'] ?? true))
                            · Cash payment: <strong>{{ ($entry['payment_status'] ?? 'unpaid') === 'paid' ? 'Paid' : 'Unpaid' }}</strong>
                        @endif
                    </div>

                    @if(
                        in_array($entry['status'], ['pending', 'confirmed', 'waitlisted'], true)
                        && $registrationOpen
                    )
                        <form action="/enrollments/{{ $entry['enrollment_id'] }}/withdraw" method="POST" data-confirm="Withdraw from this activity?">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger w-100 py-2 fw-semibold">Withdraw from activity</button>
                        </form>
                    @else
                        <div class="alert alert-secondary text-center mb-0">Withdrawal is closed for this enrollment.</div>
                    @endif
                @elseif($portal->role() !== 'senior')
                    <a href="/workspace" class="btn btn-primary w-100 py-2 fw-semibold">Back to workspace</a>
                @elseif($registrationOpen)
                    <p class="text-muted text-center mb-1">
                        {{ ($activity['confirmed'] ?? 0) >= $activity['capacity'] ? 'This activity is full. You will be placed on the waitlist.' : 'A seat is currently available.' }}
                    </p>
                    <form action="/activities/{{ $activity['activity_id'] }}/enroll" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success w-100 py-2 fw-semibold">
                            {{ ($activity['confirmed'] ?? 0) >= $activity['capacity'] ? 'Join Waitlist' : 'Enroll in Activity' }}
                        </button>
                    </form>
                @else
                    <button class="btn btn-secondary w-100 py-2 fw-semibold" disabled>Registration Closed</button>
                @endif
            </div>
        </div>
    </div>

    <section class="card shadow-sm border-0 rounded-3 bg-white mb-4 activity-discussion"
             id="discussion"
             data-discussion
             data-discussion-url="/activities/{{ $activity['activity_id'] }}/discussion">
        <div class="p-4">
            <div class="discussion-heading">
                <div>
                    <div class="small text-muted fw-semibold text-uppercase">Community conversation</div>
                    <h2 class="h4 mb-1">Discussion</h2>
                    <p class="text-muted mb-0">Ask questions, share reminders, or talk with other members about this activity.</p>
                </div>
                <span class="discussion-live">Live</span>
            </div>

            <div class="discussion-list" data-discussion-list aria-live="polite">
                <div class="discussion-empty">Loading discussion…</div>
            </div>

            <form action="/activities/{{ $activity['activity_id'] }}/discussion" method="POST" class="discussion-form">
                @csrf
                <label for="discussion-message" class="form-label">Add a comment</label>
                <textarea id="discussion-message" name="message" class="form-control" rows="3" maxlength="1000" required placeholder="Write a comment or question..."></textarea>
                <div class="discussion-form-footer">
                    <small class="text-muted">Visible to signed-in KomuniEdad members.</small>
                    <button type="submit" class="btn btn-primary">Post comment</button>
                </div>
            </form>
        </div>
    </section>

</div>

@endsection
