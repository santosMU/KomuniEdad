@extends('layout')
@section('title', request()->boolean('mine') ? 'My Activities' : 'Discover Activities')
@section('content')

<div class="px-2 pt-0 pb-2">
    @if(request()->boolean('mine'))
        @php
            $entries = collect($enrollments ?? [])->keyBy('activity_id');
            $items = collect($activities)->sortBy('start_at');
            $upcoming = $items->filter(fn ($a) => !in_array(strtolower($a['status'] ?? ''), ['completed','cancelled','archived'], true)
                && !empty($a['end_at']) && \Carbon\Carbon::parse($a['end_at'])->isFuture());
            $past = $items->reject(fn ($a) => $upcoming->contains('activity_id', $a['activity_id']));
        @endphp

        <div class="section-heading align-items-end">
            <div>
                <p class="eyebrow mb-1">MY ACTIVITIES</p>
                <h1 class="h3 mb-1">Your activity schedule</h1>
                <p class="intro mb-0">See what is coming up, your registration status, and activities you have already joined.</p>
            </div>
            <a href="/" class="btn btn-primary">Find another activity</a>
        </div>

        <section class="mb-5">
            <h2 class="fs-5 mb-3">Upcoming</h2>
            <div class="row g-3">
                @forelse($upcoming as $activity)
                    @php
                        $entry = $entries[$activity['activity_id']] ?? null;
                        $start = \Carbon\Carbon::parse($activity['start_at'])->timezone(config('app.timezone'));
                        $statusText = match($entry['status'] ?? '') {
                            'confirmed' => "You're registered",
                            'waitlisted' => "You're on the waitlist",
                            'pending' => 'Registration pending',
                            default => ucfirst($entry['status'] ?? 'Registered'),
                        };
                    @endphp
                    <div class="col-md-6 col-xl-4">
                        <article class="panel h-100 m-0 p-0 overflow-hidden">
                            <div class="my-activity-date">
                                <strong>{{ $start->format('M') }}</strong>
                                <span>{{ $start->format('j') }}</span>
                            </div>
                            <div class="p-3 pt-2">
                                <span class="badge bg-success-subtle text-success mb-2">{{ $statusText }}</span>
                                <h3 class="fs-5 mb-2">{{ $activity['title'] }}</h3>
                                <p class="mb-1"><strong>{{ $start->format('D, M j · g:i A') }}</strong></p>
                                <p class="text-muted mb-2">{{ $activity['venue'] }}</p>
                                @if(!($activity['is_free'] ?? true))
                                    <p class="small mb-3">Cash payment: <strong>{{ ($entry['payment_status'] ?? 'unpaid') === 'paid' ? 'Paid' : '₱'.number_format((float)($activity['fee'] ?? 0),2).' due onsite' }}</strong></p>
                                @else
                                    <p class="small mb-3">Free activity</p>
                                @endif
                                <a class="btn btn-outline-primary w-100" href="/activities/{{ $activity['activity_id'] }}">View details</a>
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="empty-state">
                            <h3>No upcoming activities yet.</h3>
                            <p>Browse community programs and choose something you enjoy.</p>
                            <a href="/" class="btn btn-primary">Discover activities</a>
                        </div>
                    </div>
                @endforelse
            </div>
        </section>

        @if($past->isNotEmpty())
            <section>
                <div class="section-heading">
                    <div>
                        <h2 class="fs-5 mb-1">Past and closed activities</h2>
                        <p class="text-muted mb-0">Your older registrations remain available for reference.</p>
                    </div>
                    <a href="/history" class="btn btn-outline-secondary">View participation history</a>
                </div>
                <div class="row g-3">
                    @foreach($past as $activity)
                        @php($entry = $entries[$activity['activity_id']] ?? null)
                        <div class="col-md-6 col-xl-4">
                            <article class="panel h-100 m-0">
                                <span class="badge bg-secondary-subtle text-secondary mb-2">{{ ucfirst($activity['status']) }}</span>
                                <h3 class="fs-5">{{ $activity['title'] }}</h3>
                                <p class="text-muted">{{ \Carbon\Carbon::parse($activity['start_at'])->timezone(config('app.timezone'))->format('M j, Y') }} · {{ $activity['venue'] }}</p>
                                <p class="mb-3">{{ match($entry['status'] ?? '') { 'completed' => 'Completed', 'cancelled' => 'Registration cancelled', default => ucfirst($entry['status'] ?? 'Past activity') } }}</p>
                                <a href="/activities/{{ $activity['activity_id'] }}" class="btn btn-outline-secondary">View details</a>
                            </article>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    @else
        <div class="section-heading mb-3">
            <div>
                <p class="eyebrow mb-1">DISCOVER</p>
                <h1 class="h3 mb-1">Community activities</h1>
                <p class="intro mb-0">Choose an activity that fits your interests and schedule.</p>
            </div>
            <span id="result-count" class="result-count">{{ count($activities) }} {{ count($activities) === 1 ? 'activity' : 'activities' }}</span>
        </div>

        <nav class="category-filters mb-3" aria-label="Activity categories">
            <a href="/{{ request('q') ? '?q='.urlencode(request('q')) : '' }}" class="btn btn-sm {{ !request('category') ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill">All activities</a>
            @foreach($categories as $category)
                @php
                    $catId = $category['category_id'] ?? $category['id'] ?? '';
                    $catName = trim($category['name'] ?? '');
                @endphp
                <a href="/?category={{ urlencode($catId) }}{{ request('q') ? '&q='.urlencode(request('q')) : '' }}" class="btn btn-sm {{ request('category') == $catId ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill">
                    {{ $catName }}
                </a>
            @endforeach
        </nav>

        <form class="filters senior-discovery-filters mb-3" method="get" action="/" data-activity-search>
            <input type="hidden" name="category" value="{{ request('category') }}">
            <div class="search-field">
                <label for="activity-search">Search activities</label>
                <input id="activity-search" name="q" class="form-control" maxlength="160" value="{{ $query ?? '' }}" placeholder="Search by activity or venue">
            </div>
            <div>
                <label for="activity-status">Show</label>
                <select id="activity-status" name="status" class="form-select">
                    <option value="">Available and recent</option>
                    <option value="open" @selected(request('status') === 'open')>Open for registration</option>
                    <option value="full" @selected(request('status') === 'full')>Waitlist available</option>
                    <option value="ongoing" @selected(request('status') === 'ongoing')>Ongoing</option>
                    <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                </select>
            </div>
            <button class="btn btn-primary align-self-end" type="submit">Search</button>
            <button class="btn btn-outline-secondary align-self-end" type="button" data-clear-search>Clear</button>
        </form>
        <p id="search-status" role="status" aria-live="polite"></p>
        <div id="activity-results">@include('activity-results')</div>
    @endif
</div>

@endsection
