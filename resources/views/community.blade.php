@extends('layout')
@section('title', request()->boolean('mine') ? 'My Activities' : 'Discover Activities')
@section('content')

<div class="px-2 pt-0 pb-2">
    @if(request()->boolean('mine'))
        <h2 class="fs-5 mb-1 fw-bold">My Calendar Schedule</h2>

        @php
            $sortedActivities = collect($activities)->sortBy(function($activity) {
                $start = is_array($activity) ? ($activity['start_at'] ?? $activity['date'] ?? null) : ($activity->start_at ?? null);
                return $start ? \Carbon\Carbon::parse($start)->timestamp : 0;
            })->groupBy(function($activity) {
                $start = is_array($activity) ? ($activity['start_at'] ?? $activity['date'] ?? null) : ($activity->start_at ?? null);
                return $start ? \Carbon\Carbon::parse($start)->format('F Y') : 'TBD Schedule';
            });
        @endphp

        @forelse($sortedActivities as $monthYear => $monthActivities)
            <h3 class="fs-6 fw-bold text-muted mt-3 mb-2">{{ $monthYear }}</h3>
            <div class="row g-3">
                @foreach($monthActivities as $activity)
                    @php
                        $actId = is_array($activity) ? ($activity['activity_id'] ?? $activity['id'] ?? '') : ($activity->activity_id ?? $activity->id ?? '');
                        $actTitle = is_array($activity) ? ($activity['title'] ?? '') : ($activity->title ?? '');
                        $actDesc = is_array($activity) ? ($activity['description'] ?? '') : ($activity->description ?? '');
                        $actStart = is_array($activity) ? ($activity['start_at'] ?? $activity['date'] ?? '') : ($activity->start_at ?? '');
                        $actVenue = is_array($activity) ? ($activity['venue'] ?? '') : ($activity->venue ?? '');
                       
                        $parsedDate = $actStart ? \Carbon\Carbon::parse($actStart) : null;
                       
                        $catObj = is_array($activity) ? ($activity['categories'] ?? $activity['category'] ?? null) : ($activity->categories ?? $activity->category ?? null);
                        $actCatName = is_array($catObj) ? ($catObj['name'] ?? 'General') : (is_object($catObj) ? ($catObj->name ?? 'General') : 'General');
                    @endphp
                   
                    <div class="col-md-6 col-lg-4">
                        <div class="card shadow-sm border-0 rounded-3 overflow-hidden bg-light h-100">
                            <!-- Top Date Banner Header -->
                            <div class="bg-success text-white px-3 py-2 d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-calendar-event"></i>
                                    <span class="fw-bold" style="font-size: 0.85rem;">{{ $parsedDate ? $parsedDate->format('F d, Y') : 'Date TBD' }}</span>
                                </div>
                                <span class="small fw-semibold" style="font-size: 0.85rem;"><i class="bi bi-clock me-1"></i> {{ $parsedDate ? $parsedDate->format('h:i A') : 'Time TBD' }}</span>
                            </div>

                            <!-- Content Body Below -->
                            <div class="p-3 d-flex flex-column h-100">
                                <h3 class="fs-6 fw-bold text-dark mb-1">{{ $actTitle }}</h3>
                                <p class="text-muted small mb-2">{{ $actDesc }}</p>

                                @php
                                    $actReqs = is_array($activity) ? ($activity['requirements'] ?? null) : ($activity->requirements ?? null);
                                @endphp

                                @if($actReqs)
                                    <div class="small text-success mb-2 bg-white p-2 rounded border border-success border-opacity-25">
                                        <i class="bi bi-info-circle me-1"></i> <strong>What to bring / Notes:</strong> {{ $actReqs }}
                                    </div>
                                @endif

                                <div class="small text-secondary mt-auto">
                                    <div><i class="bi bi-geo-alt me-1"></i> <strong>Venue:</strong> {{ $actVenue }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @empty
            <div class="text-center py-5 text-muted">
                <p class="small mb-0">No activities on your calendar yet.</p>
            </div>
        @endforelse

    @else
        <!-- STANDARD DISCOVER VIEW -->
        <h2 class="fs-5 mb-1 fw-bold">Community activities</h2>
        <p class="text-muted small mb-2">Filter community programs.</p>

        <!-- Category Filters -->
        <div class="d-flex flex-nowrap w-100 gap-1 mb-3">
            <a href="{{ url()->current() }}" class="btn btn-sm {{ !request('category') ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill flex-fill px-1 py-1 fw-semibold text-nowrap" style="font-size: 0.8rem;">
                All
            </a>
            @foreach($categories as $category)
                @php
                    $catId = is_array($category) ? ($category['category_id'] ?? $category['id'] ?? '') : ($category->category_id ?? $category->id ?? '');
                    $catName = is_array($category) ? ($category['name'] ?? '') : ($category->name ?? '');
                @endphp
                <a href="{{ url()->current() }}?category={{ $catId }}" class="btn btn-sm {{ request('category') == $catId ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill flex-fill px-1 py-1 fw-semibold text-nowrap" style="font-size: 0.8rem;">
                    {{ $catName }}
                </a>
            @endforeach
        </div>

        <form class="filters mb-3" method="get" action="/" data-activity-search>
            @if(request('mine'))<input type="hidden" name="mine" value="1">@endif
            <div class="search-field">
                <label for="activity-search">Search activities</label>
                <input id="activity-search" name="q" class="form-control" maxlength="160" value="{{ $query ?? '' }}" placeholder="Activity name or venue">
            </div>
            <div>
                <label for="activity-category">Category</label>
                <select id="activity-category" name="category" class="form-select">
                    <option value="">All categories</option>
                    @foreach($categories as $c)
                        @php
                            $cName = is_array($c) ? ($c['name'] ?? '') : ($c->name ?? '');
                        @endphp
                        <option value="{{ $cName }}" @selected(request('category') === $cName)>{{ $cName }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="activity-status">Status</label>
                <select id="activity-status" name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach(['open','full','ongoing','completed','cancelled'] as $state)
                        <option value="{{ $state }}" @selected(request('status') === $state)>{{ ucfirst($state) }}</option>
                    @endforeach
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
