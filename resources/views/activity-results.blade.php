@php
    $portal = app(\App\Services\Community::class);
@endphp

<div class="activity-grid">
    @if(count($activities))
        @foreach($activities as $a)
            @php
                $entry = collect($enrollments ?? [])->first(
                    fn ($e) => $e['activity_id'] === $a['activity_id'] && $e['status'] !== 'cancelled'
                );

                $img = $a['image'] ?? $a['image_url'] ?? null;
                $imgSrc = $img
                    ? (str_starts_with($img, 'data:')
                        ? $img
                        : (filter_var($img, FILTER_VALIDATE_URL) ? $img : asset('storage/'.$img)))
                    : $portal->activityImage($a);

                $status = strtolower((string) ($a['status'] ?? ''));
                $cutoffOpen = !empty($a['cutoff_at'])
                    ? \Carbon\Carbon::parse($a['cutoff_at'])->isFuture()
                    : false;
                $confirmed = (int) ($a['confirmed'] ?? 0);
                $capacity = (int) ($a['capacity'] ?? 0);
                $remaining = max(0, $capacity - $confirmed);
            @endphp

            <article class="activity-card clickable-card" data-card-href="/activities/{{ $a['activity_id'] }}" tabindex="0" role="link" aria-label="View {{ $a['title'] }}">
                <div class="card-photo">
                    <img src="{{ $imgSrc }}" alt="{{ $portal->activityImageAlt($a) }}" loading="lazy" decoding="async">
                    <span class="photo-label">{{ strtoupper($a['categories']['name'] ?? 'COMMUNITY') }}</span>
                </div>

                <div class="card-content">
                    <div class="card-meta">
                        <span class="category-pill">{{ $a['categories']['name'] ?? 'Community' }}</span>

                        @if($entry)
                            <span class="enrollment-state">{{ ucfirst($entry['status']) }}</span>
                        @elseif(!in_array($status, ['open', 'full'], true) || !$cutoffOpen)
                            <span class="availability">{{ ucfirst($status ?: 'closed') }} · Registration closed</span>
                        @elseif(array_key_exists('confirmed', $a))
                            <span class="availability">{{ $remaining > 0 ? $remaining.' spots left' : 'Waitlist available' }}</span>
                        @else
                            <span class="availability">{{ ucfirst($status) }}</span>
                        @endif
                    </div>

                    <h3><a href="/activities/{{ $a['activity_id'] }}">{{ $a['title'] }}</a></h3>
                    <p class="schedule">{{ \Carbon\Carbon::parse($a['start_at'])->timezone(config('app.timezone'))->format('D, M j · g:i A') }}</p>
                    <p class="venue">{{ $a['venue'] }}</p>
                    <p class="payment-note">{{ ($a['is_free'] ?? true) ? 'Free activity' : '₱'.number_format((float) ($a['fee'] ?? 0), 2).' cash payment onsite' }}</p>
                    <a class="card-action" href="/activities/{{ $a['activity_id'] }}">
                        {{ $entry ? 'View my enrollment' : 'View details & enroll' }}
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            </article>
        @endforeach
    @else
        <div class="empty-state">
            <h3>{{ ($mine ?? false) ? 'Your calendar is ready for something good.' : 'No activities found.' }}</h3>
            <p>{{ ($mine ?? false) ? 'Explore an activity and join when you are ready.' : 'Try another search or choose a different category.' }}</p>
            <a href="/" class="btn btn-primary">Explore all activities</a>
        </div>
    @endif
</div>
