<?php

use App\Http\Controllers\CommunityController as Community;
use App\Http\Controllers\PortalController as Portal;
use App\Http\Middleware\CommunitySession;
use App\Services\Community as CommunityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\ValidationException;

Route::view('/login', 'login')->name('login');
Route::view('/register', 'register');
Route::post('/register', [Portal::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [Community::class, 'login'])->middleware('throttle:6,1');
Route::post('/logout', function (Request $r, CommunityService $s) {
    if (! $s->demo() && $s->accessToken()) {
        try {
            $s->api('POST', '/auth/v1/logout');
        } catch (ValidationException | \Symfony\Component\HttpKernel\Exception\HttpException $e) {
        }
    }
    $r->session()->invalidate();
    $r->session()->regenerateToken();
    Cookie::queue(Cookie::forget(CommunityService::AUTH_COOKIE));

    return redirect('/login');
});
Route::post('/demo/role', function (Request $r, CommunityService $s) {
    abort_unless($s->demo(), 404);
    $d = $r->validate(['role' => 'required|in:senior,coordinator,admin']);
    session(['demo_role' => $d['role']]);

    return redirect($d['role'] === 'senior' ? '/' : '/workspace');
});
Route::get('/health', fn () => response()->json(['status' => 'ok', 'service' => 'komuniedad-laravel']));
Route::middleware([CommunitySession::class, \App\Http\Middleware\JsonFormResponse::class])->group(function () {
    Route::get('/', [Community::class, 'index']);
    Route::get('/activities/{id}', [Community::class, 'detail']);
    Route::post('/activities/{id}/enroll', [Community::class, 'enroll'])->middleware('throttle:20,1');
    Route::post('/enrollments/{id}/withdraw', [Community::class, 'withdraw']);
    Route::get('/workspace', [Portal::class, 'workspace']);
    Route::get('/workspace/create', [Portal::class, 'edit']);
    Route::post('/workspace/create', [Portal::class, 'save']);
    Route::get('/workspace/{id}/edit', [Portal::class, 'edit']);
    Route::post('/workspace/{id}/edit', [Portal::class, 'save']);
    Route::get('/workspace/{id}/participants', [Portal::class, 'roster']);
    Route::post('/workspace/{id}/attendance', [Portal::class, 'attendance']);
    Route::post('/workspace/{id}/payment', [Portal::class, 'payment']);
    Route::post('/workspace/{id}/enrollment', [Portal::class, 'enrollment']);
    Route::get('/profile', [Portal::class, 'profile']);
    Route::post('/profile', [Portal::class, 'updateProfile']);
    Route::get('/history', [Portal::class, 'history']);
    Route::post('/history/{id}/feedback', [Portal::class, 'feedback']);
    Route::get('/announcements', [Portal::class, 'announcements']);
    Route::post('/announcements', [Portal::class, 'announce']);
    Route::get('/administration', [Portal::class, 'administration']);
    Route::post('/administration/users/{id}', [Portal::class, 'user']);
    Route::post('/administration/categories', [Portal::class, 'category']);
    Route::get('/reports',[Portal::class, 'reports']);
});
