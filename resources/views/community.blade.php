@extends('layout')
@section('content')

<section id="activities">
    <div class="section-heading mb-4">
        <div>
            <h2>{{ $mine ? 'My activities' : 'Explore Activities' }}</h2>
        </div>
        <span class="result-count fs-5 fw-bold" id="result-count" aria-live="polite">{{ count($activities) }} {{ count($activities) === 1 ? 'activity' : 'activities' }}</span>
    </div>

    <form class="filters" method="get" action="/" data-activity-search>
        @if($mine)<input type="hidden" name="mine" value="1">@endif
        <div class="search-field"><label for="activity-search">Search activities</label><input id="activity-search" name="q" class="form-control" maxlength="160" value="{{ $query }}" placeholder="Activity name or venue"></div>
        <div><label for="activity-category">Category</label><select id="activity-category" name="category" class="form-select"><option value="">All categories</option>@foreach($categories as $c)<option value="{{ $c['name'] }}" @selected($category===$c['name'])>{{ $c['name'] }}</option>@endforeach</select></div>
        <div><label for="activity-status">Status</label><select id="activity-status" name="status" class="form-select"><option value="">All statuses</option>@foreach(['open','full','ongoing','completed','cancelled'] as $state)<option value="{{ $state }}" @selected($status===$state)>{{ ucfirst($state) }}</option>@endforeach</select></div>
        <button class="btn btn-primary align-self-end" type="submit">Search</button><button class="btn btn-outline-secondary align-self-end" type="button" data-clear-search>Clear</button>
    </form>
    <p id="search-status" role="status" aria-live="polite"></p>
    <div id="activity-results">@include('activity-results')</div>
</section>


@endsection
