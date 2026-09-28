@extends('layout')
@section('title', 'Sign in')

@section('content')
<style>
    .auth-topbar, .topbar { display: none !important; }
    html, body {
        height: 100vh;
        overflow: hidden;
        margin: 0;
        background: var(--paper);
    }
    .workspace {
        height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .login-panel {
        width: 100%;
        max-width: 480px;
        margin: 0 auto !important;
        box-shadow: 0 8px 28px rgba(35,62,56,.06);
    }
    @media (max-width: 576px) {
        .detail-panel.login-panel {
            border: none !important;
            box-shadow: none !important;
            background: transparent !important;
            padding: 10px 4px !important;
        }
    }
</style>

<div class="detail-panel login-panel" style="text-align: center;">
    <!-- Centered Logo and Brand Name -->
    <div style="display: flex; flex-direction: column; align-items: center; gap: 8px; margin-bottom: 20px;">
        <img src="{{ asset('images/logo.png') }}" alt="KomuniEdad Logo" style="width: 76px; height: 76px; object-fit: contain;">
        <span style="font-size: 24px; font-weight: 750; letter-spacing: -1px; color: var(--ink);">KomuniEdad</span>
    </div>
   
    <form method="post" action="/login" style="text-align: left;">
        @csrf
       
        <label for="email" class="form-label" style="font-weight: 600; color: var(--ink);">Email address</label>
        <input class="form-control mb-3" id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}" required>
       
        <label class="form-label" for="password" style="font-weight: 600; color: var(--ink);">Password</label>
        <input class="form-control mb-3" id="password" name="password" type="password" autocomplete="current-password" required>
       
        <!-- Senior-Friendly Options Group -->
        <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <input type="checkbox" id="remember" name="remember" style="width: 22px; height: 22px; cursor: pointer; accent-color: var(--green);">
                <label for="remember" style="margin: 0; font-weight: 500; font-size: 15px; color: var(--ink); cursor: pointer;">Remember me on this device</label>
            </div>

            <div style="display: flex; align-items: center; gap: 12px;">
                <input type="checkbox" id="showPassword" onclick="togglePassword()" style="width: 22px; height: 22px; cursor: pointer; accent-color: var(--green);">
                <label for="showPassword" style="margin: 0; font-weight: 500; font-size: 15px; color: var(--ink); cursor: pointer;">Show password</label>
            </div>
        </div>

        <button class="btn btn-primary w-100" style="min-height: 48px; font-size: 16px;">Sign in</button>
    </form>
   
    <p class="mt-3 mb-0" style="text-align: center; font-size: 15px;">Need an account? <a href="/register" style="font-weight: 600; color: var(--green);">Create a senior account</a></p>
</div>

<script>
function togglePassword() {
    var passwordInput = document.getElementById("password");
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
    } else {
        passwordInput.type = "password";
    }
}
</script>
@endsection