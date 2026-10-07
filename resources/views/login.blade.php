@extends('layout')
@section('title', 'Sign in')
@section('auth-shell-class', 'auth-page-shell')
@section('auth-main-class', 'auth-page-main')

@section('content')
<div class="auth-card auth-card-login">
    <section class="auth-form-panel" aria-labelledby="login-heading">
        <div class="auth-brand auth-brand-desktop">
            <img src="{{ asset('images/logo.png') }}" alt="" class="auth-logo">
            <strong>KomuniEdad</strong>
        </div>

        <div class="auth-copy">
            <h1 id="login-heading">Welcome back</h1>
            <p>Sign in to your community portal.</p>
        </div>

        @if (session('status') || session('error') || $errors->any())
            <div class="alert alert-danger auth-alert" role="alert">
                @if (session('status'))
                    {{ session('status') }}
                @elseif (session('error'))
                    {{ session('error') }}
                @else
                    Please check your email and password.
                @endif
            </div>
        @endif

        <form method="post" action="/login" class="auth-form">
            @csrf

            <div>
                <label for="email">Email address</label>
                <div class="auth-input-wrap">
                    <i class="bi bi-envelope" aria-hidden="true"></i>
                    <input class="form-control auth-input" id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}" placeholder="Enter your email address" required autofocus>
                </div>
            </div>

            <div>
                <label for="password">Password</label>
                <div class="auth-input-wrap">
                    <i class="bi bi-lock" aria-hidden="true"></i>
                    <input class="form-control auth-input" id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" required>
                </div>
            </div>

            <label class="auth-check" for="showPassword">
                <input type="checkbox" id="showPassword" data-show-password>
                <span>Show password</span>
            </label>

            <button class="btn btn-primary auth-primary-btn" type="submit">
                Sign in
                <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
            </button>
        </form>

        <div class="auth-divider" aria-hidden="true"><span>or</span></div>

        <a href="/register" class="btn auth-secondary-btn">
            <i class="bi bi-person-plus" aria-hidden="true"></i>
            Create a senior account
        </a>

        <p class="auth-help">Need help signing in? Ask your community coordinator.</p>
    </section>

    <aside class="auth-hero" aria-label="KomuniEdad community">
        <picture>
            <source media="(max-width: 767px)" srcset="https://images.pexels.com/photos/7937532/pexels-photo-7937532.jpeg?auto=compress&cs=tinysrgb&w=900">
            <img src="https://images.pexels.com/photos/8153745/pexels-photo-8153745.jpeg?auto=compress&cs=tinysrgb&w=1600" alt="Older adults enjoying time together">
        </picture>

        <div class="auth-hero-shade" aria-hidden="true"></div>

        <div class="auth-brand auth-brand-mobile">
            <img src="{{ asset('images/logo.png') }}" alt="" class="auth-logo">
            <strong>KomuniEdad</strong>
        </div>

        <div class="auth-hero-copy">
            <h2>Stay active.<br>Stay connected.</h2>
            <p>Join activities, meet people, and stay involved in your community.</p>
        </div>
    </aside>
</div>
@endsection
