<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AdminOtpNotification;

class OtpChallenge extends Component
{
    public string $code = '';
    public string $error = '';
    public string $success = '';

    public function mount(): void
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            $this->redirect('/dashboard/login');
            return;
        }

        if (session('admin_otp_verified') === true) {
            $this->redirect('/dashboard');
            return;
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
    }

    public function verify(): void
    {
        $this->error = '';
        $this->success = '';

        $this->validate([
            'code' => 'required|string|size:6',
        ]);

        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            $this->redirect('/dashboard/login');
            return;
        }

        $cacheKey = 'admin_otp_' . $admin->id;
        $attemptsKey = $cacheKey . '_attempts';

        $attempts = (int) Cache::get($attemptsKey, 0);
        if ($attempts >= 5) {
            Cache::forget($cacheKey);
            Cache::forget($attemptsKey);
            Auth::guard('admin')->logout();
            session()->invalidate();
            session()->regenerateToken();

            $this->redirect('/dashboard/login');
            return;
        }

        $storedCode = Cache::get($cacheKey);

        if (!$storedCode || !hash_equals($storedCode, $this->code)) {
            Cache::put($attemptsKey, $attempts + 1, now()->addMinutes(10));
            $remaining = 4 - $attempts;
            $this->error = "رمز التحقق غير صحيح. المحاولات المتبقية: {$remaining}";
            return;
        }

        // OTP verified
        Cache::forget($cacheKey);
        Cache::forget($attemptsKey);
        session(['admin_otp_verified' => true]);

        $this->redirect('/dashboard');
    }

    public function resend(): void
    {
        $this->error = '';
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            $this->redirect('/dashboard/login');
            return;
        }

        $cacheKey = 'admin_otp_' . $admin->id;
        $resendKey = 'admin_otp_resend_' . $admin->id;

        if (Cache::has($resendKey)) {
            $this->error = 'يرجى الانتظار 60 ثانية قبل إعادة الإرسال';
            return;
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put($cacheKey, $code, now()->addMinutes(10));
        Cache::put($cacheKey . '_attempts', 0, now()->addMinutes(10));
        Cache::put($resendKey, true, now()->addSeconds(60));

        Notification::route('mail', $admin->email)
            ->notify(new AdminOtpNotification($code, $admin->name));

        $this->success = 'تم إرسال رمز جديد إلى بريدك الإلكتروني';
    }

    public function render()
    {
        return view('livewire.otp-challenge')
            ->layout('layouts.otp');
    }
}
