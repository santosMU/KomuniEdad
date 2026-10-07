@extends('layout')
@section('title', 'Create a senior account')
@section('auth-shell-class', 'auth-page-shell')
@section('auth-main-class', 'auth-page-main')

@section('content')
<div class="auth-card auth-card-register">
    <section class="auth-form-panel" aria-labelledby="register-heading">
        <div class="auth-brand auth-brand-desktop">
            <img src="{{ asset('images/logo.png') }}" alt="" class="auth-logo">
            <strong>KomuniEdad</strong>
        </div>

        <div class="auth-copy">
            <h1 id="register-heading">Create your account</h1>
            <p>Join KomuniEdad and stay connected.</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger auth-alert" role="alert">
                Please check the information below.
            </div>
        @endif

        <form method="post" action="/register" class="auth-form">
            @csrf

            <div>
                <label for="full_name">Full name</label>
                <div class="auth-input-wrap">
                    <i class="bi bi-person" aria-hidden="true"></i>
                    <input class="form-control auth-input" id="full_name" name="full_name" type="text" autocomplete="name" value="{{ old('full_name') }}" placeholder="Enter your full name" required>
                </div>
            </div>

            <div>
                <label for="email">Email address</label>
                <div class="auth-input-wrap">
                    <i class="bi bi-envelope" aria-hidden="true"></i>
                    <input class="form-control auth-input" id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}" placeholder="Enter your email address" required>
                </div>
            </div>

            <div>
                <label for="password">Password</label>
                <div class="auth-input-wrap">
                    <i class="bi bi-lock" aria-hidden="true"></i>
                    <input class="form-control auth-input" id="password" name="password" type="password" autocomplete="new-password" placeholder="At least 12 characters" required>
                </div>
            </div>

            <div>
                <label for="password_confirmation">Confirm password</label>
                <div class="auth-input-wrap">
                    <i class="bi bi-lock" aria-hidden="true"></i>
                    <input class="form-control auth-input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Enter your password again" required>
                </div>
            </div>

            <label class="auth-check" for="showPassword">
                <input type="checkbox" id="showPassword" data-show-password>
                <span>Show passwords</span>
            </label>

            <button class="btn btn-primary auth-primary-btn" type="submit">
                Create account
                <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </button>
        </form>

        <div class="auth-divider" aria-hidden="true"><span>or</span></div>

        <a href="/login" class="btn auth-secondary-btn">Already have an account? Sign in</a>

        <p class="auth-help">Need help creating an account? Ask your community coordinator.</p>
    </section>

    <aside class="auth-hero auth-hero-register" aria-label="KomuniEdad community">
        <picture>
            <source media="(max-width: 767px)" srcset="https://images.pexels.com/photos/7937532/pexels-photo-7937532.jpeg?auto=compress&cs=tinysrgb&w=900">
            <img src="https://images.pexels.com/photos/1994934/pexels-photo-1994934.jpeg?auto=compress&cs=tinysrgb&w=1600" alt="Older adults spending time together">
        </picture>

        <div class="auth-hero-shade" aria-hidden="true"></div>

        <div class="auth-brand auth-brand-mobile">
            <img src="{{ asset('images/logo.png') }}" alt="" class="auth-logo">
            <strong>KomuniEdad</strong>
        </div>

        <div class="auth-hero-copy">
            <h2>Join your community.</h2>
            <p>Find activities, learn something new, and enjoy time with others.</p>
        </div>
    </aside>
</div>
@endsection
