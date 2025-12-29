<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class EnsureAdmin
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();
        $adminEmails = config('admin.emails', []);

        if (!$user || !in_array($user->email, $adminEmails)) {
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}
