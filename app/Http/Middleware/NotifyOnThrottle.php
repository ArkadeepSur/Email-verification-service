<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Notifications\LockoutNotification;
use App\Events\ThrottleOccurred;
use App\Models\User;

class NotifyOnThrottle
{
    public function handle($request, Closure $next)
    {
        $email = (string) $request->input('email');
        $key = Str::lower($email) . '|' . $request->ip();

        $maxAttempts = (int) config('throttle.login.max_attempts', 5);
        $decayMins = (int) config('throttle.login.decay_minutes', 1);

        // If threshold reached, send notification (but only once per decay window)
        if ($email && RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $cacheKey = 'throttle:notified:' . $key;
            if (Cache::add($cacheKey, true, $decayMins * 60)) {
                // fire event for metrics
                event(new ThrottleOccurred($key, $email, $request->ip()));

                // Increment a simple cache counter for throttle events
                Cache::increment('throttle.events.count');

                // Try to notify the user if it exists
                $user = User::where('email', $email)->first();
                if ($user) {
                    try {
                        Notification::send($user, new LockoutNotification($request->ip(), $decayMins));
                    } catch (\Exception $ex) {
                        Log::warning('Failed to send lockout notification: ' . $ex->getMessage());
                    }
                }

                // Also log a warning for operators
                Log::warning("Throttle lockout for key={$key} ip={$request->ip()} email={$email}");
            }
        }

        return $next($request);
    }
}
