<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AdminOtpNotification;

class OtpController extends Controller
{
    public function show(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return redirect('/dashboard/login');
        }

        if ($admin->isOtpVerified()) {
            return redirect('/dashboard');
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

        return view('admin.otp-verify', [
            'success' => session('otp_success'),
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|digits:6',
        ], [
            'code.required' => 'يرجى إدخال رمز التحقق',
            'code.digits'   => 'يجب أن يتكون الرمز من 6 أرقام',
        ]);

        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return redirect('/dashboard/login');
        }

        $cacheKey = 'admin_otp_' . $admin->id;
        $attemptsKey = $cacheKey . '_attempts';

        $attempts = (int) Cache::get($attemptsKey, 0);
        if ($attempts >= 5) {
            Cache::forget($cacheKey);
            Cache::forget($attemptsKey);
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/dashboard/login');
        }

        $storedCode = Cache::get($cacheKey);

        if (!$storedCode || !hash_equals($storedCode, $request->input('code'))) {
            $newAttempts = $attempts + 1;
            Cache::put($attemptsKey, $newAttempts, now()->addMinutes(10));
            $remaining = 5 - $newAttempts;

            if ($remaining <= 0) {
                Cache::forget($cacheKey);
                Cache::forget($attemptsKey);
                Auth::guard('admin')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect('/dashboard/login');
            }

            return back()->withErrors(['code' => "رمز التحقق غير صحيح. المحاولات المتبقية: {$remaining}"])->withInput();
        }

        // OTP verified — store timestamp in DB for 8 hours (survives all requests)
        Cache::forget($cacheKey);
        Cache::forget($attemptsKey);
        $admin->otp_verified_until = now()->addHours(8);
        $admin->save();

        return redirect('/dashboard');
    }

    public function resend(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return redirect('/dashboard/login');
        }

        $cacheKey = 'admin_otp_' . $admin->id;
        $resendKey = 'admin_otp_resend_' . $admin->id;

        if (Cache::has($resendKey)) {
            return back()->withErrors(['code' => 'يرجى الانتظار 60 ثانية قبل إعادة الإرسال']);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put($cacheKey, $code, now()->addMinutes(10));
        Cache::put($cacheKey . '_attempts', 0, now()->addMinutes(10));
        Cache::put($resendKey, true, now()->addSeconds(60));

        Notification::route('mail', $admin->email)
            ->notify(new AdminOtpNotification($code, $admin->name));

        return back()->with('otp_success', 'تم إرسال رمز جديد إلى بريدك الإلكتروني');
    }

    public function cancel(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if ($admin) {
            // Clear OTP cache keys and DB verification
            Cache::forget('admin_otp_' . $admin->id);
            Cache::forget('admin_otp_' . $admin->id . '_attempts');
            $admin->otp_verified_until = null;
            $admin->save();

            Auth::guard('admin')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/dashboard/login');
    }
}
