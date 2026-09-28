@extends('layout')
@section('title', 'Sign in')

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
        align-items: center !important;
        justify-content: center !important;
        width: 100% !important;
        max-width: none !important;
        padding: 16px !important;
        margin: 0 !important;
    }

    .detail-panel.login-panel {
        width: 100%;
        max-width: 460px !important;
        margin: auto !important;
        background: #ffffff !important;
        border: 1px solid rgba(0, 0, 0, 0.08) !important;
        box-shadow: 0 12px 36px rgba(35, 62, 56, 0.08) !important;
        border-radius: 18px !important;
        padding: 28px 32px !important;
        box-sizing: border-box !important;
    }

    .login-logo {
        width: 64px !important;
        height: 64px !important;
        object-fit: contain;
    }

    .login-brand-name {
        font-size: 24px !important;
        font-weight: 750;
        letter-spacing: -1px;
        color: var(--ink);
    }

    .login-label {
        font-weight: 600;
        color: var(--ink);
        font-size: 14px;
        margin-bottom: 4px !important;
    }

    .form-control.login-input,
    .form-control.login-input:focus,
    .form-control.login-input:active,
    .form-control.login-input:-webkit-autofill {
        min-height: 42px !important;
        font-size: 15px !important;
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
        width: 20px !important;
        height: 20px !important;
        cursor: pointer;
        accent-color: var(--green);
    }

    .login-checkbox-label {
        margin: 0;
        font-weight: 500;
        font-size: 14px;
        color: var(--ink);
        cursor: pointer;
    }

    .login-btn {
        min-height: 44px !important;
        font-size: 15px !important;
    }

    .login-footer {
        text-align: center;
        font-size: 14px;
        margin-top: 16px;
    }

    .login-support {
        margin-top: 16px;
        padding-top: 14px;
        border-top: 1px solid var(--line);
        font-size: 12px;
        color: var(--muted);
        text-align: center;
        line-height: 1.4;
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
            margin-bottom: 8px !important;
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
    <div style="display: flex; flex-direction: column; align-items: center; gap: 14px; margin-bottom: 24px;">
        <img src="{{ asset('images/logo.png') }}" alt="KomuniEdad Logo" class="login-logo">
        <span class="login-brand-name">KomuniEdad</span>
    </div>

    <form method="post" action="/login" style="text-align: left;">
        @csrf
        
        <label for="email" class="form-label login-label">Email address</label>
        <input class="form-control mb-3 login-input" id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}" required autofocus>

        <label for="password" class="form-label login-label">Password</label>
        <input class="form-control mb-3 login-input" id="password" name="password" type="password" autocomplete="current-password" required>

        <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <input type="checkbox" id="remember" name="remember" class="login-checkbox" {{ old('remember') ? 'checked' : '' }}>
                <label for="remember" class="login-checkbox-label">Remember me on this device</label>
            </div>
            <div style="display: flex; align-items: center; gap: 14px;">
                <input type="checkbox" id="showPassword" onclick="togglePassword()" class="login-checkbox">
                <label for="showPassword" class="login-checkbox-label">Show password</label>
            </div>
        </div>

        <button class="btn btn-primary w-100 login-btn">Sign in</button>
    </form>
    
    <p class="mb-0 login-footer">Need an account? <a href="/register" style="font-weight: 600; color: var(--green);">Create a senior account</a></p>

    <div class="login-support">
        <p style="margin: 0;">Need a hand signing in? Contact your coordinator at <strong style="color: var(--ink);">(02) 8123-4567</strong> or email <strong style="color: var(--ink);">support@komuniedad.com</strong>.</p>
    </div>
</div>

<script>
function togglePassword() {
    var p = document.getElementById("password");
    p.type = p.type === "password" ? "text" : "password";
}
</script>
@endsection