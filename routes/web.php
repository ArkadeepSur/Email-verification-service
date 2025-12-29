<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Web auth routes (session)
Route::middleware('guest')->group(function () {
    Route::get('/login', [App\Http\Controllers\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [App\Http\Controllers\AuthController::class, 'webLogin'])->middleware([\App\Http\Middleware\NotifyOnThrottle::class, 'throttle:5,1'])->name('login.attempt');

    // Registration
    Route::get('/register', [App\Http\Controllers\AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [App\Http\Controllers\AuthController::class, 'register'])->name('register.attempt');

    // Password reset (request & reset)
    Route::get('/forgot-password', [App\Http\Controllers\AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [App\Http\Controllers\AuthController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [App\Http\Controllers\AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [App\Http\Controllers\AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [App\Http\Controllers\AuthController::class, 'webLogout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () { return view('dashboard.index'); })->name('dashboard');
    Route::get('/dashboard/tokens', [App\Http\Controllers\TokenController::class, 'index'])->name('tokens.index');
    Route::post('/dashboard/tokens', [App\Http\Controllers\TokenController::class, 'store'])->name('tokens.store');
    Route::delete('/dashboard/tokens/{id}', [App\Http\Controllers\TokenController::class, 'destroy'])->name('tokens.destroy');
});

// Admin UI
Route::middleware(['auth', \App\Http\Middleware\EnsureAdmin::class])->prefix('admin')->group(function () {
    Route::get('/throttles', [App\Http\Controllers\Admin\ThrottleController::class, 'index'])->name('admin.throttles');
    Route::get('/throttles/export', [App\Http\Controllers\Admin\ThrottleController::class, 'exportCsv'])->name('admin.throttles.export');
    Route::get('/throttles/data', [App\Http\Controllers\Admin\ThrottleController::class, 'data'])->name('admin.throttles.data');
});

// Development helper: quick login as seeded admin (local only)
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
