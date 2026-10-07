@if(count($comments ?? []))
@foreach($comments as $comment)
<article class="discussion-comment">
    <div class="discussion-comment-head">
        <strong>{{ $comment['author_name'] ?? 'Community member' }}</strong>
        @if(!empty($comment['author_role']) && $comment['author_role'] !== 'senior')
            <span class="discussion-role">{{ ucfirst($comment['author_role']) }}</span>
        @endif
        <small>{{ \Carbon\Carbon::parse($comment['created_at'])->timezone(config('app.timezone'))->diffForHumans() }}</small>
    </div>
    <p>{{ $comment['message'] }}</p>
</article>
@endforeach
@else
<div class="discussion-empty">No comments yet. You can be the first to ask a question.</div>
@endif
