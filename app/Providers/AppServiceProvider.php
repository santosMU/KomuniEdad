<?php

namespace App\Providers;

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The request attribute is set only after remote identity and active-profile validation.
        Auth::viaRequest('supabase', function ($request) {
            $profile = $request->attributes->get('verified_profile');

            return $profile ? new GenericUser(['id' => $profile['user_id']] + $profile) : null;
        });
    }
}
