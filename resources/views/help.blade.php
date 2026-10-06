@extends('layout')
@section('title', 'Help & FAQ')

@section('content')
<div class="mb-2">
    <span class="eyebrow" style="color: var(--green); font-size: 0.75rem;">SUPPORT CENTER</span>
    <h1 class="fs-5 fw-bold mb-0">Frequently Asked Questions</h1>
</div>

<div class="panel p-3 mb-2">
    <div style="display: flex; flex-direction: column; gap: 12px;">
        <div>
            <h3 class="fs-6 fw-bold mb-1" style="color: var(--ink);">How do I join a community activity?</h3>
            <p class="text-muted small mb-0" style="line-height: 1.4;">
                Go to the <span class="d-md-none"><strong>Discover</strong> tab</span><span class="d-none d-md-inline"><strong>Discover activities</strong></span> and click <strong>View details & enroll</strong> on the activity card.
            </p>
        </div>

        <hr style="border-color: var(--line); margin: 0;">

        <div>
            <h3 class="fs-6 fw-bold mb-1" style="color: var(--ink);">Where can I see my signed-up activities?</h3>
            <p class="text-muted small mb-0" style="line-height: 1.4;">
                Click on <span class="d-md-none"><strong>My Activities</strong></span><span class="d-none d-md-inline"><strong>My activities</strong></span> to view all upcoming and past events you are enrolled in.
            </p>
        </div>

        <hr style="border-color: var(--line); margin: 0;">

        <div>
            <h3 class="fs-6 fw-bold mb-1" style="color: var(--ink);">How do I update my profile details?</h3>
            <p class="text-muted small mb-0" style="line-height: 1.4;">
                Select <span class="d-md-none">the <strong>Profile</strong> tab</span><span class="d-none d-md-inline"><strong>My profile</strong></span> to manage your account preferences.
            </p>
        </div>
    </div>
</div>

<div class="panel p-3" style="background: #e9eddc; border: none;">
    <h2 class="fs-6 fw-bold mb-1" style="color: var(--ink);">Still need assistance?</h2>
    <p class="small mb-2" style="color: #4c6251; line-height: 1.4;">
        Our coordinators are ready to help with registration or program questions.
    </p>
    <div class="small fw-bold" style="color: var(--ink);">
        📞 (02) 8123-4567 &nbsp;|&nbsp; ✉️ support@komuniedad.com
    </div>
</div>
@endsection