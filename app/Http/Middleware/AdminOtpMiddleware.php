<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AdminOtpNotification;
use Symfony\Component\HttpFoundation\Response;

class AdminOtpMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return $next($request);
        }

        // Allow access to OTP verification page and logout
        if ($request->routeIs('filament.admin.otp-challenge') || $request->routeIs('filament.admin.auth.*')) {
            return $next($request);
        }

        // Check if OTP already verified this session
        if (session('admin_otp_verified') === true) {
            return $next($request);
        }

        // Generate OTP if not already pending
        $cacheKey = 'admin_otp_' . $admin->id;
        if (!Cache::has($cacheKey)) {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            Cache::put($cacheKey, $code, now()->addMinutes(10));
            Cache::put($cacheKey . '_attempts', 0, now()->addMinutes(10));

            Notification::route('mail', $admin->email)
                ->notify(new AdminOtpNotification($code, $admin->name));
        }

        return redirect()->route('filament.admin.otp-challenge');
    }
}
