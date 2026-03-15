<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        if ($request->is('otp-verify*') || $request->is('dashboard/login') || $request->is('dashboard/logout') || $request->routeIs('admin.otp-verify*') || $request->routeIs('filament.admin.auth.*')) {
            return $next($request);
        }

        // Check if OTP already verified (stored in DB — survives across all middleware stacks)
        if ($admin->isOtpVerified()) {
            return $next($request);
        }

        return redirect('/otp-verify');
    }
}
