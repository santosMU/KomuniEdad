@extends('layout')
@section('title', 'Help & FAQ')

@section('content')
<div class="heading mb-4">
    <span class="eyebrow" style="color: var(--green);">SUPPORT CENTER</span>
    <h1>Frequently Asked Questions</h1>
    <p class="intro">Find simple answers to common questions about joining activities, managing your profile, and getting assistance.</p>
</div>

<div class="panel">
    <h2 style="font-size: 22px; margin-bottom: 24px; color: var(--green);">Using KomuniEdad</h2>
    
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <div>
            <h3 style="font-size: 18px; font-weight: 650; margin-bottom: 8px;">How do I join a community activity?</h3>
            <p style="color: var(--muted); font-size: 16px; line-height: 1.6; margin: 0;">
                Go to the <strong>Discover activities</strong> page from the menu. Browse through the available events and click on an activity title to view full details, then click the <strong>Join activity</strong> button.
            </p>
        </div>

        <hr style="border-color: var(--line); margin: 0;">

        <div>
            <h3 style="font-size: 18px; font-weight: 650; margin-bottom: 8px;">Where can I see the activities I have signed up for?</h3>
            <p style="color: var(--muted); font-size: 16px; line-height: 1.6; margin: 0;">
                Click on <strong>My activities</strong> in the side menu. This page displays all upcoming and past activities you are currently enrolled in.
            </p>
        </div>

        <hr style="border-color: var(--line); margin: 0;">

        <div>
            <h3 style="font-size: 18px; font-weight: 650; margin-bottom: 8px;">How do I update my personal profile or contact details?</h3>
            <p style="color: var(--muted); font-size: 16px; line-height: 1.6; margin: 0;">
                Select <strong>My profile</strong> from the navigation menu to view your account details and update your contact preferences.
            </p>
        </div>
    </div>
</div>

<div class="panel" style="background: #e9eddc; border: none;">
    <h2 style="font-size: 22px; margin-bottom: 12px; color: var(--ink);">Still need assistance?</h2>
    <p style="color: #4c6251; font-size: 16px; line-height: 1.7; margin-bottom: 20px;">
        If you are having trouble signing in, need help registering, or have questions about a community program, our coordinators are ready to help.
    </p>
    <div style="font-size: 17px; font-weight: 650; color: var(--ink);">
        📞 Phone: (02) 8123-4567<br>
        ✉️ Email: support@komuniedad.com
    </div>
</div>
@endsection