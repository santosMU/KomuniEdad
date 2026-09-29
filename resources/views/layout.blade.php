<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="theme-color" content="#245c48">
    <title>@yield('title','Activities') · KomuniEdad</title>
    <link rel="icon" type="image/png" href="/images/logo.png">
    <link rel="stylesheet" href="/vendor/bootstrap.min.css">
    <link rel="stylesheet" href="/community.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="/community.js" defer></script>
    <style>
        @media (min-width: 768px) and (max-width: 1200px) {
            .auth-page input.form-control {
                font-size: 26px !important;
                min-height: 74px !important;
            }
        }
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus {
            -webkit-text-fill-color: var(--ink) !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        /* Locks the app shell and prevents background mirroring/scaling glitches */
        .app-shell {
            display: flex !important;
            height: 100vh !important;
            width: 100vw !important;
            overflow: hidden !important;
            position: relative !important;
        }
       .workspace {
            flex: 1 !important;
            height: 100vh !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            position: relative !important;
            transform: none !important;
            direction: ltr !important;
            box-sizing: border-box !important; /* Ensures padding is contained within 100vh */
        }
    </style>

    <style>
        /* Fixes Chrome DevTools iPad emulation rendering bug (mirrored/ghosted repaint) */
        .topbar, .sidebar-backdrop {
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }
        .app-shell, .workspace, main, header {
            transform: none !important;
            filter: none !important;
        }

        /* Mobile & Tablet Bottom Navigation Bar & Topbar Styles */
        .mobile-bottom-nav {
            display: none;
        }
        @media (max-width: 1024px) {
            /* Hide the hamburger menu button and sidebar */
            .menu-toggle {
                display: none !important;
            }
            .sidebar {
                display: none !important;
            }

            /* Style the mobile topbar: Logo + Name on left, Date on right */
            .topbar {
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                padding: 0 16px !important;
            }
            .topbar-start {
                display: flex !important;
                align-items: center !important;
            }
            .topbar-brand {
                display: flex !important;
                align-items: center;
                gap: 6px;
                text-decoration: none;
            }
            .topbar-brand img {
                width: 38px !important;
                height: auto;
                margin: 0 !important;
            }
            .topbar-brand strong {
                font-size: 16px;
                color: #245c48;
                font-weight: 700;
            }
            .portal-label {
                display: none !important;
            }
        
            /* Force topbar-end and date to be visible */
            .topbar-end {
                display: flex !important;
                align-items: center !important;
            }
            .topbar-end .today {
                display: inline-block !important;
                visibility: visible !important;
                font-size: 12px !important;
                color: #4a5568 !important;
                font-weight: 600 !important;
            }
            .mobile-signout {
                display: none !important;
            }

            /* Mobile Bottom Navigation Bar Styles */
            .mobile-bottom-nav {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                height: 70px;
                background: #ffffff;
                border-top: 2px solid #e2e8f0;
                display: flex;
                justify-content: space-around;
                align-items: center;
                z-index: 1050;
                box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.05);
            }
            .mobile-bottom-nav a {
                flex: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                color: #4a5568;
                text-decoration: none;
                font-size: 13px;
                font-weight: 600;
                padding: 8px 0;
            }
            .mobile-bottom-nav a.active {
                color: #245c48;
                background: #f0fdf4;
            }
            .mobile-bottom-nav .nav-icon {
                font-size: 24px;
                margin-bottom: 2px;
            }
            .workspace {
                padding-bottom: 70px !important;
                box-sizing: border-box !important;
            }
        }
    </style>
</head>
@php
    $portal = app(\App\Services\Community::class);
    $demo = $portal->demo();
    $guestPage = request()->is('login', 'register');
    $currentRole = $guestPage ? null : $portal->role();
@endphp
<body class="{{ $guestPage ? 'auth-page' : '' }} @yield('body-class')">
<a class="skip" href="#main">Skip to content</a>

@if($guestPage)
<div class="auth-shell @yield('auth-shell-class')">
    <header class="auth-topbar">
        <a class="brand" href="/login">
            <img src="/images/logo.png" alt="" aria-hidden="true" style="width: 65px; height: auto; margin-right: -5px; margin-left: -5px;">
            <span>KomuniEdad</span>
        </a>
        <span class="portal-label">Senior Citizen Community Portal</span>
    </header>

    <div id="request-status" role="status" aria-live="polite" tabindex="-1" hidden></div>
    <main id="main" class="auth-main @yield('auth-main-class')">
        @if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
        @yield('content')
    </main>
</div>
@else
<div class="app-shell" data-app-shell>
    <aside class="sidebar" id="site-sidebar" aria-label="Community navigation">
        <div class="sidebar-header" style="margin-bottom: 24px;">
            <a class="brand" href="/">
                <img src="/images/logo.png" alt="" aria-hidden="true" style="width: 65px; height: auto; margin-right: -2px; margin-left: -5px;">
                <span>KomuniEdad</span>
            </a>
            <button class="sidebar-close" type="button" data-sidebar-dismiss aria-label="Close navigation"><span aria-hidden="true">×</span></button>
        </div>
       <nav aria-label="Main navigation">
            @if($currentRole==='senior')
                <a class="nav-item {{ request()->is('/') && !request()->boolean('mine') ? 'selected' : '' }}" href="/">Discover activities</a>
                <a class="nav-item {{ request()->boolean('mine') ? 'selected' : '' }}" href="/?mine=1">My activities</a>
                <a class="nav-item {{ request()->is('history') ? 'selected' : '' }}" href="/history">Participation history</a>
            @else
                <a class="nav-item {{ request()->is('workspace*') ? 'selected' : '' }}" href="/workspace">Program workspace</a>
                <a class="nav-item {{ request()->is('reports') ? 'selected' : '' }}" href="/reports">Participation reports</a>
            @endif
            <a class="nav-item {{ request()->is('announcements') ? 'selected' : '' }}" href="/announcements">Announcements</a>
            <a class="nav-item {{ request()->is('profile') ? 'selected' : '' }}" href="/profile">My profile</a>
            <a class="nav-item {{ request()->is('help') ? 'selected' : '' }}" href="/help">Help & FAQ</a>
            @if($currentRole==='admin')
                <a class="nav-item {{ request()->is('administration*') ? 'selected' : '' }}" href="/administration">Administration</a>
            @endif
        </nav>

        <div class="sidebar-bottom">
            <div class="help-card">
                <span class="help-symbol" aria-hidden="true">♡</span>
                <strong>A little help goes a long way.</strong>
                <p>Need a hand joining an activity? Ask your community coordinator.</p>
            </div>
            <div class="member">
                <span class="avatar">{{ strtoupper(substr(session('profile.full_name','Demo member'),0,1)) }}</span>
                <div>
                    <strong>{{ $demo ? 'Demo ' . ucfirst($currentRole) : session('profile.full_name','Welcome') }}</strong>
                    <small>{{ ucfirst($currentRole) }} {{ $demo ? '· Demo' : '' }}</small>
                </div>
            </div>
            @if(!$demo && $portal->accessToken())
                <form method="post" action="/logout">@csrf<button class="btn btn-link">Sign out</button></form>
            @endif
        </div>
    </aside>

    <button class="sidebar-backdrop" type="button" data-sidebar-dismiss aria-label="Close navigation" tabindex="-1"></button>

    <div class="workspace">
        <header class="topbar">
            <div class="topbar-start">
                <button class="menu-toggle" type="button" data-sidebar-toggle aria-controls="site-sidebar" aria-expanded="true">
                    <span class="menu-icon" aria-hidden="true"><i></i><i></i><i></i></span>
                    <span class="menu-label">Menu</span>
                </button>
                <a class="topbar-brand" href="/" aria-label="KomuniEdad home">
                    <img src="/images/logo.png" alt="" aria-hidden="true" style="width: 65px; height: auto; margin-right: -5px; margin-left: -5px;">
                    <strong>KomuniEdad</strong>
                </a>
                <span class="portal-label">Senior Citizen Community Portal</span>
            </div>
            <div class="topbar-end">
                @if(!$demo && $portal->accessToken())
                    <form method="post" action="/logout" class="mobile-signout">@csrf<button class="btn btn-outline-secondary">Sign out</button></form>
                @endif
                <span class="today">{{ now()->format('l, F j') }}</span>
            </div>
        </header>

        <div id="request-status" role="status" aria-live="polite" tabindex="-1" hidden></div>
        <main id="main">
            @if($demo)
                <div class="demo-toolbar">
                    <div class="demo-label"><span class="status-dot"></span> DEMO PREVIEW <span>Sample data saved in this browser session. No live records are changed.</span></div>
                    <form method="post" action="/demo/role">
                        @csrf
                        <label for="demo-role">Preview as</label>
                        <select class="form-select" id="demo-role" name="role">
                            @foreach(['senior','coordinator','admin'] as $r)<option @selected($currentRole===$r)>{{ $r }}</option>@endforeach
                        </select>
                        <button class="btn btn-outline-secondary">Switch role</button>
                    </form>
                </div>
            @endif
            @if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
            @yield('content')
        </main>
    </div>

    <!-- Mobile Bottom Navigation Bar (Visible only on smaller phone screens) -->
    @if($currentRole === 'senior')
    <nav class="mobile-bottom-nav" aria-label="Mobile bottom navigation">
        <a href="/" class="{{ request()->is('/') && !request()->boolean('mine') ? 'active' : '' }}">
            <i class="nav-icon bi bi-house-door-fill" aria-hidden="true"></i>
            <span>Discover</span>
        </a>
        <a href="/?mine=1" class="{{ request()->boolean('mine') ? 'active' : '' }}">
            <i class="nav-icon bi bi-calendar-check-fill" aria-hidden="true"></i>
            <span>My Activities</span>
        </a>
        <a href="/help" class="{{ request()->is('help') ? 'active' : '' }}">
            <i class="nav-icon bi bi-question-circle-fill" aria-hidden="true"></i>
            <span>Help & FAQ</span>
        </a>
        <a href="/profile" class="{{ request()->is('profile') ? 'active' : '' }}">
            <i class="nav-icon bi bi-person-fill" aria-hidden="true"></i>
            <span>Profile</span>
        </a>
    </nav>
    @endif
</div>
@endif
</body>
</html>