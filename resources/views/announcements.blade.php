@extends('layout')
@section('title','Announcements')
@section('content')

<div class="section-heading align-items-end">
    <div>
        <p class="eyebrow mb-1">ANNOUNCEMENTS</p>
        <h1 class="mb-1">Community updates</h1>
        <p class="intro mb-0">Important reminders, schedule changes, and activity news.</p>
    </div>
</div>

@if(in_array($role,['admin','coordinator']))
    <details class="panel">
        <summary>Post an announcement</summary>
        <form method="post" class="form-grid mt-3">
            @csrf
            <div>
                <label for="new-title">Title</label>
                <input class="form-control" name="title" id="new-title" required maxlength="160">
            </div>
            <div>
                <label for="new-activity">Audience</label>
                <select class="form-select" name="activity_id" id="new-activity" @required($role==='coordinator')>
                    @if($role==='admin')<option value="">All community members</option>@endif
                    @foreach($activities as $a)
                        <option value="{{ $a['activity_id'] }}">{{ $a['title'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="wide">
                <label for="new-message">Message</label>
                <textarea class="form-control" name="message" id="new-message" rows="3" required maxlength="5000"></textarea>
            </div>
            <div class="wide"><button class="btn btn-primary">Publish announcement</button></div>
        </form>
    </details>
@endif

<div class="announcement-list">
    @forelse($announcements as $n)
        @if(!$n['archived_at'] || $role !== 'senior')
            @php
                $activity = !empty($n['activity_id']) ? ($activityDirectory[$n['activity_id']] ?? null) : null;
                $titleLower = strtolower($n['title']);
                $urgent = str_contains($titleLower,'cancel') || str_contains($titleLower,'change') || str_contains($titleLower,'urgent');
            @endphp
            <article class="panel announcement-card {{ $urgent ? 'announcement-urgent' : '' }}">
                <div class="announcement-meta">
                    <span class="badge {{ $urgent ? 'bg-warning-subtle text-dark' : 'bg-success-subtle text-success' }}">
                        {{ $n['archived_at'] ? 'Archived' : ($urgent ? 'Important update' : 'Community update') }}
                    </span>
                    <span>{{ CarbonCarbon::parse($n['posted_at'])->timezone(config('app.timezone'))->format('M j, Y') }}</span>
                </div>

                <h2 class="fs-4 mb-2">{{ $n['title'] }}</h2>
                @if($activity)
                    <p class="small text-muted mb-2">For: <strong>{{ $activity['title'] }}</strong></p>
                @elseif(empty($n['activity_id']))
                    <p class="small text-muted mb-2">For: <strong>Everyone</strong></p>
                @endif
                <p class="announcement-message">{{ $n['message'] }}</p>

                @if($role==='admin' || ($role==='coordinator' && collect($activities)->contains('activity_id',$n['activity_id'])))
                    <details class="mt-3">
                        <summary>Edit or archive</summary>
                        <form method="post" class="form-grid mt-3">
                            @csrf
                            <input type="hidden" name="announcement_id" value="{{ $n['announcement_id'] }}">
                            <input type="hidden" name="activity_id" value="{{ $n['activity_id'] }}">
                            <div class="wide">
                                <label for="title-{{ $n['announcement_id'] }}">Title</label>
                                <input class="form-control" id="title-{{ $n['announcement_id'] }}" name="title" required maxlength="160" value="{{ $n['title'] }}">
                            </div>
                            <div class="wide">
                                <label for="message-{{ $n['announcement_id'] }}">Message</label>
                                <textarea class="form-control" id="message-{{ $n['announcement_id'] }}" name="message" required maxlength="5000">{{ $n['message'] }}</textarea>
                            </div>
                            <div>
                                <label for="archived-{{ $n['announcement_id'] }}">Visibility</label>
                                <select class="form-select" id="archived-{{ $n['announcement_id'] }}" name="archived">
                                    <option value="0">Published</option>
                                    <option value="1" @selected($n['archived_at'])>Archived</option>
                                </select>
                            </div>
                            <div><button class="btn btn-primary">Save announcement</button></div>
                        </form>
                    </details>
                @endif
            </article>
        @endif
    @empty
        <div class="empty-state">
            <h3>No announcements yet.</h3>
            <p>Important community updates will appear here.</p>
        </div>
    @endforelse
</div>

@endsection
