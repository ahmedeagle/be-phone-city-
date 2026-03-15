<?php

use Illuminate\Support\Facades\Route;
// Route::get('/', function () {
//     return view('welcome');
// });

// Admin Routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    // Order Print Route - Protected by Filament auth (handled in controller)
    Route::get('/orders/{order}/print', [\App\Http\Controllers\Admin\OrderPrintController::class, 'print'])
        ->name('orders.print');

    // Payment Proof Route - Protected by Filament auth (handled in controller)
    Route::get('/payment-transactions/{transaction}/proof', [\App\Http\Controllers\Admin\PaymentProofController::class, 'show'])
        ->name('payment-transactions.proof');
});

// SECURITY: Artisan routes removed - they were publicly accessible without authentication.
// Use `php artisan` CLI commands directly on the server instead.

// OTP Challenge route - standalone, outside Filament's /dashboard path
// Uses 'web' middleware for session/CSRF (required by Livewire)
// No auth:admin — the Livewire component handles auth check in mount()
Route::get('/otp-verify', \App\Livewire\OtpChallenge::class)
    ->middleware('web')
    ->name('admin.otp-challenge');

// Serve static assets from frontend directory
Route::get('/assets/{path}', function ($path) {
    $filePath = public_path("frontend/assets/{$path}");

    if (!file_exists($filePath)) {
        abort(404);
    }

    // Determine MIME type based on file extension
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'js' => 'application/javascript',
        'mjs' => 'application/javascript',
        'css' => 'text/css',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
        'json' => 'application/json',
        'html' => 'text/html',
        'ico' => 'image/x-icon',
    ];

    $mimeType = $mimeTypes[$extension] ?? mime_content_type($filePath) ?? 'application/octet-stream';

    return response()->file($filePath, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000', // Cache for 1 year
    ]);
})->where('path', '.*');

// Catch-all route for React app (must be last)
Route::get('/{any}', function () {
    return file_get_contents(public_path('frontend/index.html'));
})->where('any', '.*');
