<?php

namespace App\Http\Middleware;

use App\Services\Community;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ValidateRecordIds
{
    public function handle(Request $request, Closure $next)
    {
        if (!app(Community::class)->demo()) {
            $values = $request->only(['category_id', 'coordinator_id', 'senior_id', 'enrollment_id', 'activity_id', 'announcement_id']);
            if ($request->route('id') !== null) $values['id'] = $request->route('id');
            Validator::make($values, array_fill_keys(array_keys($values), 'nullable|uuid'))->validate();
        }
        return $next($request);
    }
}
