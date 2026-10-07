@extends('layout')
@section('title','My profile')
@section('content')

@php
    $portal = app(\App\Services\Community::class);
    $photoUrl = $portal->profilePhotoUrl($profile['avatar_path'] ?? null);
    $initial = strtoupper(substr($profile['full_name'] ?? 'M', 0, 1));
@endphp

<div class="px-1 py-2 my-auto w-100">
    <div class="section-heading align-items-end">
        <div>
            <p class="eyebrow mb-1">MY PROFILE</p>
            <h1 class="h3 mb-1">My profile</h1>
            <p class="text-muted mb-0">Keep your contact details up to date.</p>
        </div>
    </div>

    <nav class="senior-profile-links" aria-label="Profile shortcuts">
        @if($profile['role']==='senior')<a class="btn btn-outline-secondary" href="/history">Past activities</a>@endif
        <a class="btn btn-outline-secondary" href="/announcements">Updates</a>
    </nav>

    <form class="panel form-grid senior-profile-form m-0" method="post" action="/profile" enctype="multipart/form-data">
        @csrf

        <div class="wide profile-photo-section">
            <div class="profile-photo-preview" aria-hidden="true">
                @if($photoUrl)
                    <img src="{{ $photoUrl }}" alt="">
                @else
                    <span>{{ $initial }}</span>
                @endif
            </div>
            <div class="profile-photo-copy">
                <p class="form-label fw-bold mb-2">Your photo</p>
                <div class="profile-photo-picker">
                    <label class="btn btn-outline-primary profile-photo-button" for="profile_photo">Choose photo</label>
                    <span class="profile-photo-file-name" data-profile-photo-name>No new photo selected</span>
                    <input class="visually-hidden" type="file" name="profile_photo" id="profile_photo" accept="image/jpeg,image/png,image/webp" data-profile-photo>
                </div>
                <p class="small text-muted mb-0 mt-2">Choose a JPG, PNG, or WebP photo up to 10 MB. You can change it anytime.</p>
            </div>
        </div>

        <div class="mb-2">
            <label class="form-label fw-bold mb-1" for="full_name">Full name</label>
            <input class="form-control py-2" name="full_name" id="full_name" required maxlength="120" value="{{ old('full_name',$profile['full_name']) }}">
        </div>

        <div class="mb-2">
            <label class="form-label fw-bold mb-1" for="contact_number">Contact number (optional)</label>
            <input class="form-control py-2" name="contact_number" id="contact_number" maxlength="30" value="{{ old('contact_number',$profile['contact_number']??'') }}">
        </div>

        @if($profile['role']==='senior')
            <div class="mb-2">
                <label class="form-label fw-bold mb-1" for="birthdate">Birthdate (optional)</label>
                <input class="form-control py-2" type="date" name="birthdate" id="birthdate" max="{{ now()->toDateString() }}" value="{{ old('birthdate',$senior['birthdate']??'') }}">
            </div>

            <div class="mb-2">
                <label class="form-label fw-bold mb-1" for="address">Address (optional)</label>
                <input class="form-control py-2" name="address" id="address" maxlength="500" value="{{ old('address',$senior['address']??'') }}">
            </div>

            <p class="small text-muted mb-2">Account verification: <strong class="text-dark">{{ ucfirst($senior['verification_status']??'pending') }}</strong></p>
        @endif

        <div class="wide d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary flex-grow-1 fw-bold">Save my profile</button>

            @if(!$demo && $portal->accessToken())
                <button type="submit" form="profile-logout" class="btn btn-outline-danger flex-grow-1 fw-bold">Sign out</button>
            @endif
        </div>
    </form>

    @if(!$demo && $portal->accessToken())
        <form id="profile-logout" method="post" action="/logout">@csrf</form>
    @endif
</div>

@endsection
