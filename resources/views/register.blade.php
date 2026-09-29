@extends('layout')
@section('title', 'Create a senior account')

@section('auth-shell-class', 'login-shell-override')
@section('auth-main-class', 'login-main-override')

@section('content')
<style>
    .auth-topbar { display: none !important; }
    
    html, body {
        height: 100vh;
        overflow: hidden;
        margin: 0;
        background: var(--paper);
    }

    .login-shell-override {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        min-height: 100vh !important;
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        background: var(--paper) !important;
    }

    .login-main-override {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        width: 100% !important;
        max-width: none !important;
        padding: 24px !important;
        margin: 0 !important;
    }

    .detail-panel.login-panel {
        width: 100%;
        max-width: 480px !important;
        margin: auto !important;
        background: #ffffff !important;
        border: 1px solid rgba(0, 0, 0, 0.08) !important;
        box-shadow: 0 12px 36px rgba(35, 62, 56, 0.08) !important;
        border-radius: 18px !important;
        padding: 36px !important;
        box-sizing: border-box !important;
    }

    .login-logo {
        width: 76px !important;
        height: 76px !important;
        object-fit: contain;
    }

    .login-brand-name {
        font-size: 26px !important;
        font-weight: 750;
        letter-spacing: -1px;
        color: var(--ink);
    }

    .login-label {
        font-weight: 600;
        color: var(--ink);
        font-size: 15px;
    }

    .form-control.login-input,
    .form-control.login-input:focus,
    .form-control.login-input:active,
    .form-control.login-input:-webkit-autofill {
        min-height: 48px !important;
        font-size: 16px !important;
    }

    input[type="password"]::-ms-reveal,
    input[type="password"]::-ms-clear,
    input[type="password"]::-webkit-credentials-auto-fill-button,
    input[type="password"]::-webkit-strong-password-auto-fill-button {
        display: none !important;
        visibility: hidden !important;
        pointer-events: none !important;
    }

    .login-checkbox {
        width: 22px !important;
        height: 22px !important;
        cursor: pointer;
        accent-color: var(--green);
    }

    .login-checkbox-label {
        margin: 0;
        font-weight: 500;
        font-size: 15px;
        color: var(--ink);
        cursor: pointer;
    }

    .login-btn {
        min-height: 48px !important;
        font-size: 16px !important;
    }

    .login-footer {
        text-align: center;
        font-size: 15px;
        margin-top: 24px;
    }

    .login-support {
        margin-top: 22px;
        padding-top: 18px;
        border-top: 1px solid var(--line);
        font-size: 13px;
        color: var(--muted);
        text-align: center;
        line-height: 1.5;
    }

    @media (min-width: 768px) and (max-width: 1200px) {
        .detail-panel.login-panel {
            max-width: 980px !important;
            padding: 70px 96px !important;
            border-radius: 32px !important;
        }
        .login-logo {
            width: 150px !important;
            height: 150px !important;
        }
        .login-brand-name {
            font-size: 48px !important;
        }
        .login-label {
            font-size: 26px !important;
        }
        .form-control.login-input,
        .form-control.login-input:focus,
        .form-control.login-input:active,
        .form-control.login-input:-webkit-autofill,
        .form-control.login-input:-webkit-autofill:hover,
        .form-control.login-input:-webkit-autofill:focus {
            min-height: 74px !important;
            font-size: 26px !important;
            padding: 18px 28px !important;
            -webkit-text-fill-color: var(--ink) !important;
        }
        .login-checkbox {
            width: 36px !important;
            height: 36px !important;
        }
        .login-checkbox-label {
            font-size: 24px !important;
        }
        .login-btn {
            min-height: 76px !important;
            font-size: 26px !important;
            margin-top: 20px !important;
        }
        .login-footer {
            font-size: 24px !important;
            margin-top: 36px !important;
        }
        .login-support {
            font-size: 22px !important;
            margin-top: 32px !important;
            padding-top: 26px !important;
        }
    }

    @media (max-width: 576px) {
        .detail-panel.login-panel {
            border: none !important;
            box-shadow: none !important;
            background: transparent !important;
            padding: 10px 16px !important;
            max-width: 100% !important;
        }
    }
</style>

<div class="detail-panel login-panel" style="text-align: center;">
    <div style="display: flex; flex-direction: column; align-items: center; gap: 20px; margin-bottom: 36px;">
        <img src="{{ asset('images/logo.png') }}" alt="KomuniEdad Logo" class="login-logo">
        <span class="login-brand-name">Create a senior account</span>
    </div>
   
    <form method="post" action="/register" style="text-align: left;">
        @csrf
       
        <label for="full_name" class="form-label login-label">Full name</label>
        <input class="form-control mb-4 login-input" id="full_name" name="full_name" type="text" autocomplete="name" value="{{ old('full_name') }}" required>

        <label for="email" class="form-label login-label">Email address</label>
        <input class="form-control mb-4 login-input" id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}" required>
       
        <label for="password" class="form-label login-label">Password (at least 12 characters)</label>
        <input class="form-control mb-4 login-input" id="password" name="password" type="password" autocomplete="new-password" required>

        <label for="password_confirmation" class="form-label login-label">Confirm password</label>
        <input class="form-control mb-4 login-input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>

        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 34px;">
            <input type="checkbox" id="showPassword" onclick="togglePassword()" class="login-checkbox">
            <label for="showPassword" class="login-checkbox-label">Show passwords</label>
        </div>

        <button class="btn btn-primary w-100 login-btn">Create account</button>
    </form>
   
    <p class="mb-0 login-footer">Already have an account? <a href="/login" style="font-weight: 600; color: var(--green);">Sign in</a></p>

    <div class="login-support">
        <p style="margin: 0;">Need a hand signing up? Contact your coordinator at <strong style="color: var(--ink);">(02) 8123-4567</strong> or email <strong style="color: var(--ink);">support@komuniedad.com</strong>.</p>
    </div>
</div>

<script>
function togglePassword() {
    var p1 = document.getElementById("password");
    var p2 = document.getElementById("password_confirmation");
    var type = p1.type === "password" ? "text" : "password";
    p1.type = type;
    p2.type = type;
}
</script>
@endsection
