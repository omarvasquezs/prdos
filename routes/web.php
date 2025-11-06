<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;

// Rutas de autenticación
Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF cookie set']);
})->name('csrf-cookie');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/logout', [AuthController::class, 'logout'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Rutas protegidas (requieren autenticación)
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);
    // Route to show the role selection SPA route (served by the admin SPA)
    Route::get('/select-role', [DashboardController::class, 'index']);
    Route::get('/test', [DashboardController::class, 'index']);
});

// API routes para Vue
Route::middleware('auth')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])->group(function () {
    Route::get('/api/user', [AuthController::class, 'user']);
    Route::post('/select-role', [AuthController::class, 'setActiveRole']);
    Route::post('/api/set-active-role', [AuthController::class, 'setActiveRole']);
});
