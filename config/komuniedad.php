<?php

return [
    'demo' => env('KOMUNIEDAD_DEMO', false),
    'url' => env('SUPABASE_URL', env('NEXT_PUBLIC_SUPABASE_URL')),
    'key' => env(
        'SUPABASE_ANON_KEY',
        env('SUPABASE_PUBLISHABLE_KEY', env('NEXT_PUBLIC_SUPABASE_ANON_KEY'))
    ),
];
