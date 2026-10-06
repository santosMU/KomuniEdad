@extends('layout')
@section('title', request()->boolean('mine') ? 'My Activities' : 'Discover Activities')
@section('content')

<div class="px-2 pt-0 pb-2">
    @if(request()->boolean('mine'))
        <h2 class="fs-5 mb-1 fw-bold">My Calendar Schedule</h2>

        @php
            // Sort activities chronologically by start date
            $sortedActivities = collect($activities)->sortBy(function($activity) {
                $start = is_array($activity) ? ($activity['startat'] ?? $activity['start_at'] ?? $activity['date'] ?? null) : ($activity->start_at ?? $activity->startat ?? null);
                return $start ? \Carbon\Carbon::parse($start)->timestamp : 0;
            })->groupBy(function($activity) {
                $start = is_array($activity) ? ($activity['startat'] ?? $activity['start_at'] ?? $activity['date'] ?? null) : ($activity->start_at ?? $activity->startat ?? null);
                return $start ? \Carbon\Carbon::parse($start)->format('F Y') : 'TBD Schedule';
            });
        @endphp

        @forelse($sortedActivities as $monthYear => $monthActivities)
            <div class="d-flex flex-column gap-3 w-100 mt-3">
                @foreach($monthActivities as $activity)
                    @php
                        $actId = is_array($activity) ? ($activity['activity_id'] ?? $activity['id'] ?? '') : ($activity->activity_id ?? $activity->id ?? '');
                        $actTitle = is_array($activity) ? ($activity['title'] ?? '') : ($activity->title ?? '');
                        $actDesc = is_array($activity) ? ($activity['description'] ?? '') : ($activity->description ?? '');
                        $actStart = is_array($activity) ? ($activity['startat'] ?? $activity['start_at'] ?? $activity['date'] ?? '') : ($activity->start_at ?? $activity->startat ?? '');
                        $actVenue = is_array($activity) ? ($activity['venue'] ?? '') : ($activity->venue ?? '');
                       
                        $parsedDate = $actStart ? \Carbon\Carbon::parse($actStart) : null;
                       
                        $catObj = is_array($activity) ? ($activity['category'] ?? null) : ($activity->category ?? null);
                        $actCatName = is_array($catObj) ? ($catObj['name'] ?? 'General') : (is_object($catObj) ? ($catObj->name ?? 'General') : 'General');
                    @endphp
                   
                    <!-- Calendar Date Card Stack (Top Header Banner) -->
                    <div class="card shadow-sm border-0 rounded-3 overflow-hidden bg-light w-100 mb-2">
                        <!-- Top Date Banner Header -->
                        <div class="bg-success text-white px-3 py-2 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-calendar-event"></i>
                                <span class="fw-bold" style="font-size: 0.85rem;">{{ $parsedDate ? $parsedDate->format('F d, Y') : 'Date TBD' }}</span>
                            </div>
                            <span class="small fw-semibold" style="font-size: 0.85rem;"><i class="bi bi-clock me-1"></i> {{ $parsedDate ? $parsedDate->format('h:i A') : 'Time TBD' }}</span>
                        </div>

                        <!-- Content Body Below -->
                        <div class="p-3">
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

                            <div class="small text-secondary">
                                <div><i class="bi bi-geo-alt me-1"></i> <strong>Venue:</strong> {{ $actVenue }}</div>
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

        <!-- Activity List -->
        <div class="d-flex flex-column gap-3">
            @forelse($activities as $activity)
                @php
                    $actId = is_array($activity) ? ($activity['activity_id'] ?? $activity['id'] ?? '') : ($activity->activity_id ?? $activity->id ?? '');
                    $actStatus = is_array($activity) ? ($activity['status'] ?? 'Open') : ($activity->status ?? 'Open');
                    $actTitle = is_array($activity) ? ($activity['title'] ?? '') : ($activity->title ?? '');
                    $actDesc = is_array($activity) ? ($activity['description'] ?? '') : ($activity->description ?? '');
                    $actStart = is_array($activity) ? ($activity['startat'] ?? $activity['start_at'] ?? $activity['date'] ?? '') : ($activity->start_at ?? $activity->startat ?? '');
                    $actVenue = is_array($activity) ? ($activity['venue'] ?? '') : ($activity->venue ?? '');
                   
                    $actImage = is_array($activity) ? ($activity['image'] ?? $activity['imageurl'] ?? null) : ($activity->image ?? $activity->image_url ?? null);
                    if (empty($actImage)) {
                        $actImage = 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=600&q=80';
                    }
                   
                    $catObj = is_array($activity) ? ($activity['category'] ?? null) : ($activity->category ?? null);
                    $actCatName = is_array($catObj) ? ($catObj['name'] ?? 'General') : (is_object($catObj) ? ($catObj->name ?? 'General') : 'General');
                @endphp
                <div class="card shadow-sm border-0 rounded-3 overflow-hidden bg-light">
                    <img src="{{ filter_var($actImage, FILTER_VALIDATE_URL) ? $actImage : asset('storage/' . $actImage) }}" class="card-img-top" style="height: 140px; object-fit: cover;" alt="{{ $actTitle }}">
                    <div class="p-3">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <span class="badge bg-secondary mb-1">{{ $actCatName }}</span>
                            <span class="badge {{ $actStatus === 'Open' ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ ucfirst($actStatus) }}
                            </span>
                        </div>
                       
                        <h3 class="fs-6 fw-bold text-dark mb-1">{{ $actTitle }}</h3>
                        <p class="text-muted small mb-2">{{ $actDesc }}</p>
                       
                        <div class="small text-secondary mb-3">
                            <div><strong>Schedule:</strong> {{ $actStart ? \Carbon\Carbon::parse($actStart)->format('M d, Y • h:i A') : 'TBD' }}</div>
                            <div><strong>Venue:</strong> {{ $actVenue }}</div>
                        </div>

                        <a href="{{ url('/activities/' . $actId) }}" class="btn btn-primary btn-sm fw-bold py-2 w-100">
                            View details & enroll
                        </a>
                    </div>
                </div>
            @empty
                <div class="text-center py-5 text-muted">
                    <p class="small mb-0">No activities found.</p>
                </div>
            @endforelse
        </div>
    @endif
</div>

@endsection