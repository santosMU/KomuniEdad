@extends('layout')
@section('content')

<section id="activities">
    <div class="section-heading mb-4">
        <div>
            <h2>{{ $mine ? 'My activities' : 'Explore Activities' }}</h2>
        </div>
        <span class="result-count fs-5 fw-bold" id="result-count" aria-live="polite">{{ count($activities) }} {{ count($activities) === 1 ? 'activity' : 'activities' }}</span>
    </div>

    <p id="search-status" role="status" aria-live="polite"></p>
    <div id="activity-results">@include('activity-results')</div>
</section>


@endsection