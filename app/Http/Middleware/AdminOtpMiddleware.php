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

        // Allow access to OTP verification page, login, and logout
        if ($request->is('otp-verify') || $request->is('dashboard/login') || $request->is('dashboard/logout') || $request->routeIs('admin.otp-challenge') || $request->routeIs('filament.admin.auth.*')) {
            return $next($request);
        }

        // Check if OTP already verified this session
        if (session('admin_otp_verified') === true) {
            return $next($request);
        }

        return redirect('/otp-verify');
    }
}
