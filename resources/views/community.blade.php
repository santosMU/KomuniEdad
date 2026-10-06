@extends('layout')
@section('title', 'Discover Activities')
@section('content')

<div class="px-2 py-3">
    <h2 class="fs-5 mb-1 fw-bold">Community activities</h2>
    <p class="text-muted small mb-3">Search and filter community programs.</p>

    <!-- Search and Category Filters Form -->
    <form method="GET" action="{{ url()->current() }}" class="mb-3">
        <div class="input-group mb-2">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search activities..." value="{{ request('search') }}">
            <button class="btn btn-primary btn-sm" type="submit">Search</button>
        </div>
        <div class="d-flex flex-wrap gap-1">
            <a href="{{ url()->current() }}" class="btn btn-sm {{ !request('category') ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill px-3 py-1 small">All</a>
            @foreach($categories as $category)
                @php
                    $catId = is_array($category) ? ($category['category_id'] ?? $category['id'] ?? '') : ($category->category_id ?? $category->id ?? '');
                    $catName = is_array($category) ? ($category['name'] ?? '') : ($category->name ?? '');
                @endphp
                <a href="{{ url()->current() }}?category={{ $catId }}{{ request('search') ? '&search='.request('search') : '' }}" class="btn btn-sm {{ request('category') == $catId ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill px-3 py-1 small">
                    {{ $catName }}
                </a>
            @endforeach
        </div>
    </form>

    <!-- Activity List -->
    <div class="d-flex flex-column gap-3">
        @forelse($activities as $activity)
            @php
                $actId = is_array($activity) ? ($activity['activity_id'] ?? $activity['id'] ?? '') : ($activity->activity_id ?? $activity->id ?? '');
                $actStatus = is_array($activity) ? ($activity['status'] ?? 'Open') : ($activity->status ?? 'Open');
                $actTitle = is_array($activity) ? ($activity['title'] ?? '') : ($activity->title ?? '');
                $actDesc = is_array($activity) ? ($activity['description'] ?? '') : ($activity->description ?? '');
                $actStart = is_array($activity) ? ($activity['start_at'] ?? '') : ($activity->start_at ?? '');
                $actVenue = is_array($activity) ? ($activity['venue'] ?? '') : ($activity->venue ?? '');
                
                // Get image from DB, or fallback to a default community placeholder image if empty
                $actImage = is_array($activity) ? ($activity['image'] ?? $activity['image_url'] ?? null) : ($activity->image ?? $activity->image_url ?? null);
                if (empty($actImage)) {
                    $actImage = 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=600&q=80';
                }
                
                $catObj = is_array($activity) ? ($activity['category'] ?? null) : ($activity->category ?? null);
                if (is_array($catObj)) {
                    $actCatName = $catObj['name'] ?? 'General';
                } elseif (is_object($catObj)) {
                    $actCatName = $catObj->name ?? 'General';
                } else {
                    $actCatName = 'General';
                }
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
                    <p class="text-muted small mb-2 text-truncate">{{ $actDesc }}</p>
                    
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
</div>

@endsection