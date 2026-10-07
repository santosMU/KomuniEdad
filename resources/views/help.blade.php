@extends('layout')
@section('title', 'Help')

@section('content')
<div class="senior-page-heading">
    <div>
        <p class="eyebrow mb-1">HELP</p>
        <h1 class="mb-2">Need help using KomuniEdad?</h1>
        <p class="intro mb-0">Follow these simple steps. You can also contact a coordinator if you would rather speak with someone.</p>
    </div>
</div>

<div class="senior-help-grid">
    <article class="panel senior-help-card">
        <span class="senior-step-number" aria-hidden="true">1</span>
        <div>
            <h2>Join an activity</h2>
            <ol class="senior-steps">
                <li>Choose <strong>Activities</strong>.</li>
                <li>Find an activity you like and choose <strong>View activity</strong>.</li>
                <li>Read the date, place, and cost.</li>
                <li>Choose <strong>Join this activity</strong>.</li>
            </ol>
        </div>
    </article>

    <article class="panel senior-help-card">
        <span class="senior-step-number" aria-hidden="true">2</span>
        <div>
            <h2>Check what you joined</h2>
            <p>Choose <strong>My schedule</strong> to see upcoming activities and your registration status.</p>
            <a href="/?mine=1" class="btn btn-outline-primary">Open my schedule</a>
        </div>
    </article>

    <article class="panel senior-help-card">
        <span class="senior-step-number" aria-hidden="true">3</span>
        <div>
            <h2>Read important updates</h2>
            <p>Choose <strong>Updates</strong> to check schedule changes, cancellations, and community reminders.</p>
            <a href="/announcements" class="btn btn-outline-primary">Open updates</a>
        </div>
    </article>

    <article class="panel senior-help-card">
        <span class="senior-step-number" aria-hidden="true">4</span>
        <div>
            <h2>Change your information</h2>
            <p>Choose <strong>Profile</strong> to update your name, phone number, address, birthdate, or profile photo.</p>
            <a href="/profile" class="btn btn-outline-primary">Open my profile</a>
        </div>
    </article>
</div>

<section class="panel senior-contact-card">
    <div>
        <p class="eyebrow mb-1">PERSONAL HELP</p>
        <h2>Would you rather speak with someone?</h2>
        <p class="mb-0">A community coordinator can help with signing in, registration, or activity questions.</p>
    </div>
    <div class="senior-contact-options">
        <a href="tel:+63281234567" class="btn btn-primary"><i class="bi bi-telephone" aria-hidden="true"></i> Call (02) 8123-4567</a>
        <a href="mailto:support@komuniedad.com" class="btn btn-outline-primary"><i class="bi bi-envelope" aria-hidden="true"></i> Email support</a>
    </div>
</section>
@endsection
