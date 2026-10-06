@extends('layout')
@section('title', $activity['title'])
@section('content')

@php
    $portal = app(\App\Services\Community::class);
    $img = $activity['image'] ?? $activity['image_url'] ?? null;
    $src = $img 
        ? (str_starts_with($img, 'data:') ? $img : (filter_var($img, FILTER_VALIDATE_URL) ? $img : asset('storage/' . $img))) 
        : $portal->activityImage($activity);
@endphp

<div class="container py-3" style="max-width: 800px;">
    <!-- Back Link -->
    <div class="mb-3">
        <a href="/" class="text-decoration-none fw-semibold text-secondary">
            <span aria-hidden="true">←</span> Back to activities
        </a>
    </div>

    <!-- Activity Detail Card -->
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden bg-white mb-4">
        <img src="{{ $src }}" class="w-100 object-fit-cover" style="height: 300px;" alt="{{ $portal->activityImageAlt($activity) }}">
        
        <div class="p-4">
            <!-- Category & Status Badges -->
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-secondary">{{ $activity['categories']['name'] ?? 'Community' }}</span>
                <span class="badge {{ strtolower($activity['status']) === 'open' ? 'bg-success' : 'bg-warning text-dark' }}">
                    {{ ucfirst($activity['status']) }}
                </span>
            </div>

            <!-- Title & Description -->
            <h1 class="h3 fw-bold text-dark mb-2">{{ $activity['title'] }}</h1>
            <p class="text-muted mb-4">{{ $activity['description'] }}</p>

            <!-- Schedule & Venue Grid -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6">
                    <div class="small text-muted fw-semibold text-uppercase">When</div>
                    <div class="text-dark fw-medium">{{ \Carbon\Carbon::parse($activity['start_at'])->timezone(config('app.timezone'))->format('l, F j · g:i A') }}</div>
                </div>
                <div class="col-sm-6">
                    <div class="small text-muted fw-semibold text-uppercase">Where</div>
                    <div class="text-dark fw-medium">{{ $activity['venue'] }}</div>
                </div>
            </div>

            <!-- Requirements / Notes -->
            @if(!empty($activity['requirements']))
                <div class="alert alert-light border border-success border-opacity-25 text-success mb-4">
                    <strong>What to bring / Notes:</strong> {{ $activity['requirements'] }}
                </div>
            @endif

            <!-- Enrollment Actions -->
            <div class="d-grid gap-2">
                @php
                    $entry = collect($enrollments ?? [])->first(fn($e) => $e['activity_id'] === $activity['activity_id'] && $e['status'] !== 'cancelled');
                @endphp

                @if($entry)
                    <div class="alert alert-success text-center mb-3 py-2">
                        You are enrolled! Status: <strong>{{ ucfirst($entry['status']) }}</strong>
                    </div>
                    <form action="/activities/{{ $activity['activity_id'] }}/withdraw" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger w-100 py-2 fw-semibold">Withdraw from activity</button>
                    </form>
                @elseif($activity['status'] === 'open' || $activity['status'] === 'full')
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
</div>

@endsection