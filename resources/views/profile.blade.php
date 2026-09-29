@extends('layout')
@section('title','My profile')
@section('content')

<style>
    @media (max-width: 1024px) {
        .workspace {
            padding-bottom: 70px !important;
            box-sizing: border-box !important;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100vh !important;
        }
    }
</style>

<div class="px-1 py-2 my-auto w-100">
    <h2 class="fs-5 mb-1 fw-bold">Your profile</h2>
    <p class="text-muted small mb-3">Keep your contact details up to date.</p>

    <form class="panel form-grid bg-light p-3 rounded-3 shadow-sm m-0" method="post" action="/profile">
        @csrf
        
        <div class="mb-2">
            <label class="form-label small fw-bold mb-1" for="full_name">Full name</label>
            <input class="form-control form-control-sm py-2" name="full_name" id="full_name" required maxlength="120" value="{{ old('full_name',$profile['full_name']) }}">
        </div>

        <div class="mb-2">
            <label class="form-label small fw-bold mb-1" for="contact_number">Contact number (optional)</label>
            <input class="form-control form-control-sm py-2" name="contact_number" id="contact_number" maxlength="30" value="{{ old('contact_number',$profile['contact_number']??'') }}">
        </div>

        @if($profile['role']==='senior')
            <div class="mb-2">
                <label class="form-label small fw-bold mb-1" for="birthdate">Birthdate (optional)</label>
                <input class="form-control form-control-sm py-2" type="date" name="birthdate" id="birthdate" max="{{ now()->toDateString() }}" value="{{ old('birthdate',$senior['birthdate']??'') }}">
            </div>

            <div class="mb-2">
                <label class="form-label small fw-bold mb-1" for="address">Address (optional)</label>
                <input class="form-control form-control-sm py-2" name="address" id="address" maxlength="500" value="{{ old('address',$senior['address']??'') }}">
            </div>

            <p class="small text-muted mb-2">Status: <strong class="text-dark">{{ ucfirst($senior['verification_status']??'pending') }}</strong></p>
        @endif

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary btn-sm flex-grow-1 py-2 fw-bold">Save profile</button>
            
            @if(!$demo && app(\App\Services\Community::class)->accessToken())
                <form method="post" action="/logout" class="flex-grow-1 m-0">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100 py-2 fw-bold">Sign out</button>
                </form>
            @endif
        </div>
    </form>
</div>

@endsection