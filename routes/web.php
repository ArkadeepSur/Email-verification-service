<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Health Check
|--------------------------------------------------------------------------
*/
Route::get('/health/db', function () {
    DB::connection()->getPdo();
    return 'DB CONNECTED';
});

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Guest Web Auth
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [App\Http\Controllers\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [App\Http\Controllers\AuthController::class, 'webLogin'])
        ->middleware([\App\Http\Middleware\NotifyOnThrottle::class, 'throttle:5,1'])
        ->name('login.attempt');

    Route::get('/register', [App\Http\Controllers\AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [App\Http\Controllers\AuthController::class, 'register'])->name('register.attempt');

    Route::get('/forgot-password', [App\Http\Controllers\AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [App\Http\Controllers\AuthController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [App\Http\Controllers\AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [App\Http\Controllers\AuthController::class, 'resetPassword'])->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Email Verification (OFFICIAL LARAVEL)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect('/dashboard');
    })->middleware('signed')->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('message', 'Verification link sent!');
    })->middleware('throttle:6,1')->name('verification.send');
});

/*
|--------------------------------------------------------------------------
| Authenticated + Verified
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard.index');
    })->name('dashboard');

    Route::get('/dashboard/tokens', [App\Http\Controllers\TokenController::class, 'index'])->name('tokens.index');
    Route::post('/dashboard/tokens', [App\Http\Controllers\TokenController::class, 'store'])->name('tokens.store');
    Route::delete('/dashboard/tokens/{id}', [App\Http\Controllers\TokenController::class, 'destroy'])->name('tokens.destroy');
});

Route::post('/logout', [App\Http\Controllers\AuthController::class, 'webLogout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', \App\Http\Middleware\EnsureAdmin::class])
    ->prefix('admin')
    ->group(function () {
        Route::get('/throttles', [App\Http\Controllers\Admin\ThrottleController::class, 'index'])->name('admin.throttles');
        Route::get('/throttles/export', [App\Http\Controllers\Admin\ThrottleController::class, 'exportCsv'])->name('admin.throttles.export');
        Route::get('/throttles/data', [App\Http\Controllers\Admin\ThrottleController::class, 'data'])->name('admin.throttles.data');
    });

/*
|--------------------------------------------------------------------------
| Local Dev Helper
|--------------------------------------------------------------------------
*/
if (env('APP_ENV') === 'local') {
    Route::get('/dev/login', function () {
        $user = App\Models\User::where('email', 'admin@example.com')->first();
        if ($user) {
            \Illuminate\Support\Facades\Auth::login($user);
            return redirect('/dashboard');
        }

        return 'No dev user found';
    });
}
