<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#245c48"><title>@yield('title','Activities') · KomuniEdad</title><link rel="icon" type="image/svg+xml" href="https://upload.wikimedia.org/wikipedia/commons/5/5a/Sun_Symbol_of_the_National_Flag_of_the_Philippines.svg"><link rel="stylesheet" href="/vendor/bootstrap.min.css"><link rel="stylesheet" href="/community.css"><meta name="csrf-token" content="{{ csrf_token() }}"><script src="/community.js" defer></script></head>
<body><a class="skip" href="#main">Skip to content</a>
@php($portal=app(\App\Services\Community::class))
@php($demo=$portal->demo())
@php($currentRole=$portal->role())
<div class="app-shell" data-app-shell>
<aside class="sidebar" id="site-sidebar" aria-label="Community navigation">
<div class="sidebar-header"><a class="brand" href="/"><span class="brand-mark"><img src="https://upload.wikimedia.org/wikipedia/commons/5/5a/Sun_Symbol_of_the_National_Flag_of_the_Philippines.svg" alt="" aria-hidden="true"></span><span>KomuniEdad</span></a><button class="sidebar-close" type="button" data-sidebar-dismiss aria-label="Close navigation"><span aria-hidden="true">×</span></button></div>
<p class="brand-note">A community for every chapter.</p><div class="nav-label">YOUR COMMUNITY</div>
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
@if($currentRole==='admin')<a class="nav-item {{ request()->is('administration*') ? 'selected' : '' }}" href="/administration">Administration</a>@endif
</nav>
<div class="sidebar-bottom"><div class="help-card"><span class="help-symbol" aria-hidden="true">♡</span><strong>A little help goes a long way.</strong><p>Need a hand joining an activity? Ask your community coordinator.</p></div>
<div class="member"><span class="avatar">{{ strtoupper(substr(session('profile.full_name','Demo member'),0,1)) }}</span><div><strong>{{ $demo ? 'Demo '.ucfirst($currentRole) : session('profile.full_name','Welcome') }}</strong><small>{{ ucfirst($currentRole) }} {{ $demo ? '· Demo' : '' }}</small></div></div>
@if(!$demo && session('access_token'))<form method="post" action="/logout">@csrf<button class="btn btn-link">Sign out</button></form>@endif
</div></aside>
<button class="sidebar-backdrop" type="button" data-sidebar-dismiss aria-label="Close navigation" tabindex="-1"></button>
<div class="workspace"><header class="topbar"><div class="topbar-start"><button class="menu-toggle" type="button" data-sidebar-toggle aria-controls="site-sidebar" aria-expanded="true"><span class="menu-icon" aria-hidden="true"><i></i><i></i><i></i></span><span class="menu-label">Menu</span></button><a class="topbar-brand" href="/" aria-label="KomuniEdad home"><span class="topbar-brand-mark"><img src="https://upload.wikimedia.org/wikipedia/commons/5/5a/Sun_Symbol_of_the_National_Flag_of_the_Philippines.svg" alt="" aria-hidden="true"></span><strong>KomuniEdad</strong></a><span class="portal-label">Senior citizen community portal</span></div><div class="topbar-end">@if(!$demo && session('access_token'))<form method="post" action="/logout" class="mobile-signout">@csrf<button class="btn btn-outline-secondary">Sign out</button></form>@endif<span class="today">{{ now()->format('l, F j') }}</span></div></header>
<div id="request-status" role="status" aria-live="polite" tabindex="-1" hidden></div><main id="main">
@if($demo)<div class="demo-toolbar"><div class="demo-label"><span class="status-dot"></span> DEMO PREVIEW <span>Sample data saved in this browser session. No live records are changed.</span></div><form method="post" action="/demo/role">@csrf<label for="demo-role">Preview as</label><select class="form-select" id="demo-role" name="role">@foreach(['senior','coordinator','admin'] as $r)<option @selected($currentRole===$r)>{{ $r }}</option>@endforeach</select><button class="btn btn-outline-secondary">Switch role</button></form></div>@endif
@if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
@yield('content')</main><footer>KomuniEdad <span>Made for connection. Built around you.</span></footer></div></div></body></html>
