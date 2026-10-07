@if(count($comments ?? []))
@foreach($comments as $comment)
<article class="discussion-comment">
  <div>
    <strong>{{ $comment['author_name'] ?? 'Community member' }}</strong>
    <small>{{ \Carbon\Carbon::parse($comment['created_at'])->timezone(config('app.timezone'))->diffForHumans() }}</small>
    <p>{{ $comment['message'] }}</p>
  </div>
</article>
@endforeach
@else
<div class="discussion-empty">No comments yet. Start the conversation.</div>
@endif
