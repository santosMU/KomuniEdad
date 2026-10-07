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
    $statusLabel = match($status) {
        'open' => 'Open for registration',
        'full' => 'Waitlist available',
        'ongoing' => 'Happening now',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        default => ucfirst($status ?: 'Activity'),
    };
    $registrationOpen = in_array($status, ['open', 'full'], true)
        && !empty($activity['cutoff_at'])
        && \Carbon\Carbon::parse($activity['cutoff_at'])->isFuture();

    $entry = collect($enrollments ?? [])->first(
        fn ($e) => $e['activity_id'] === $activity['activity_id'] && $e['status'] !== 'cancelled'
    );
    $coordinatorPhoto = $portal->profilePhotoUrl($activity['coordinator_avatar_path'] ?? null);
@endphp

<div class="container py-3 senior-detail-page" style="max-width: 900px;">
    <div class="mb-3">
        <a href="/" class="senior-back-link">
            <span aria-hidden="true">←</span> Back to activities
        </a>
    </div>

    <div class="card shadow-sm border-0 rounded-3 overflow-hidden bg-white mb-4">
        <img src="{{ $src }}" class="w-100 object-fit-cover" style="height: 300px;" alt="{{ $portal->activityImageAlt($activity) }}">

        <div class="p-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-secondary">{{ $activity['categories']['name'] ?? 'Community' }}</span>
                <span class="badge {{ $status === 'open' ? 'bg-success' : 'bg-warning text-dark' }}">
                    {{ $statusLabel }}
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
                    <div class="small text-muted fw-semibold text-uppercase">Date and time</div>
                    <div class="text-dark fw-medium">
                        {{ \Carbon\Carbon::parse($activity['start_at'])->timezone(config('app.timezone'))->format('l, F j · g:i A') }}
                        –
                        {{ \Carbon\Carbon::parse($activity['end_at'])->timezone(config('app.timezone'))->format('g:i A') }}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="small text-muted fw-semibold text-uppercase">Location</div>
                    <div class="text-dark fw-medium">{{ $activity['venue'] }}</div>
                </div>
                <div class="col-sm-6">
                    <div class="small text-muted fw-semibold text-uppercase">Registration deadline</div>
                    <div class="text-dark fw-medium">{{ \Carbon\Carbon::parse($activity['cutoff_at'])->timezone(config('app.timezone'))->format('M j, g:i A') }}</div>
                </div>
                <div class="col-sm-6">
                    <div class="small text-muted fw-semibold text-uppercase">Cost</div>
                    <div class="text-dark fw-medium">
                        @if($activity['is_free'] ?? true)
                            Free to join
                        @else
                            ₱{{ number_format((float) ($activity['fee'] ?? 0), 2) }} cash, paid in person
                        @endif
                    </div>
                </div>
            </div>

            <div class="alert alert-light border border-success border-opacity-25 text-success mb-3">
                <strong>What to bring:</strong>
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
                    <div class="small text-muted fw-semibold text-uppercase">Activity coordinator</div>
                    <strong>{{ $activity['coordinator_name'] ?? 'Community coordinator' }}</strong>
                    <p class="small text-muted mb-0">Contact this coordinator if you need help with this activity.</p>
                </div>
            </div>

            <div class="d-grid gap-2">
                @if($entry)
                    <div class="alert alert-success text-center mb-1 py-2">
                        Your registration: <strong>{{ ucfirst($entry['status']) }}</strong>
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
                            <button type="submit" class="btn btn-outline-danger w-100 senior-secondary-action">Withdraw from activity</button>
                        </form>
                    @else
                        <div class="alert alert-secondary text-center mb-0">Withdrawal is closed for this enrollment.</div>
                    @endif
                @elseif($portal->role() !== 'senior')
                    <a href="/workspace" class="btn btn-primary w-100 senior-primary-action">Back to workspace</a>
                @elseif($registrationOpen)
                    <p class="text-muted text-center mb-1">
                        {{ ($activity['confirmed'] ?? 0) >= $activity['capacity'] ? 'This activity is full. You will be placed on the waitlist.' : 'A seat is currently available.' }}
                    </p>
                    <form action="/activities/{{ $activity['activity_id'] }}/enroll" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success w-100 senior-primary-action">
                            {{ ($activity['confirmed'] ?? 0) >= $activity['capacity'] ? 'Join the waitlist' : 'Join this activity' }}
                        </button>
                    </form>
                @else
                    <button class="btn btn-secondary w-100 py-2 fw-semibold" disabled>Registration is closed</button>
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
                    <div class="small text-muted fw-semibold text-uppercase">ACTIVITY DISCUSSION</div>
                    <h2 class="h3 mb-1">Comments and questions</h2>
                    <p class="text-muted mb-0">Ask a question or share something helpful with people joining this activity.</p>
                </div>
                <span class="discussion-live">Updates automatically</span>
            </div>

            <div class="discussion-list" data-discussion-list aria-live="polite">
                <div class="discussion-empty">Loading discussion…</div>
            </div>

            <form action="/activities/{{ $activity['activity_id'] }}/discussion" method="POST" class="discussion-form">
                @csrf
                <label for="discussion-message" class="form-label">Write a comment or question</label>
                <textarea id="discussion-message" name="message" class="form-control" rows="3" maxlength="1000" required placeholder="Type your comment or question here"></textarea>
                <div class="discussion-form-footer">
                    <small class="text-muted">Other signed-in community members can read this.</small>
                    <button type="submit" class="btn btn-primary">Post my comment</button>
                </div>
            </form>
        </div>
    </section>

</div>

@endsection
