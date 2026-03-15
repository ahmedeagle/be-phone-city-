<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Notifications\AdminOtpNotification;

class OtpChallenge extends Page
{
    protected string $view = 'filament.pages.otp-challenge';

    protected static ?string $title = 'التحقق بالرمز';

    protected static string $layout = 'filament-panels::components.layout.base';

    protected static bool $shouldRegisterNavigation = false;

    public ?string $code = '';

    public function mount(): void
    {
        if (session('admin_otp_verified') === true) {
            $this->redirect(filament()->getUrl());
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->label('رمز التحقق')
                    ->placeholder('أدخل الرمز المكون من 6 أرقام')
                    ->required()
                    ->length(6)
                    ->numeric()
                    ->autocomplete('one-time-code')
                    ->extraInputAttributes(['class' => 'text-center text-2xl tracking-widest', 'dir' => 'ltr']),
            ]);
    }

    public function verify(): void
    {
        $data = $this->form->getState();
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            $this->redirect(filament()->getLoginUrl());
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

            Notification::make()
                ->title('تم تجاوز عدد المحاولات المسموحة')
                ->body('يرجى تسجيل الدخول مرة أخرى.')
                ->danger()
                ->send();

            $this->redirect(filament()->getLoginUrl());
            return;
        }

        $storedCode = Cache::get($cacheKey);

        if (!$storedCode || !hash_equals($storedCode, $data['code'])) {
            Cache::put($attemptsKey, $attempts + 1, now()->addMinutes(10));

            Notification::make()
                ->title('رمز التحقق غير صحيح')
                ->body('يرجى المحاولة مرة أخرى. المحاولات المتبقية: ' . (4 - $attempts))
                ->danger()
                ->send();

            return;
        }

        // OTP verified
        Cache::forget($cacheKey);
        Cache::forget($attemptsKey);
        session(['admin_otp_verified' => true]);

        Notification::make()
            ->title('تم التحقق بنجاح')
            ->success()
            ->send();

        $this->redirect(filament()->getUrl());
    }

    public function resend(): void
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            $this->redirect(filament()->getLoginUrl());
            return;
        }

        $cacheKey = 'admin_otp_' . $admin->id;
        $resendKey = 'admin_otp_resend_' . $admin->id;

        // Rate limit: 1 resend per 60 seconds
        if (Cache::has($resendKey)) {
            Notification::make()
                ->title('يرجى الانتظار قبل إعادة الإرسال')
                ->warning()
                ->send();
            return;
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put($cacheKey, $code, now()->addMinutes(10));
        Cache::put($cacheKey . '_attempts', 0, now()->addMinutes(10));
        Cache::put($resendKey, true, now()->addSeconds(60));

        \Illuminate\Support\Facades\Notification::route('mail', $admin->email)
            ->notify(new AdminOtpNotification($code, $admin->name));

        Notification::make()
            ->title('تم إرسال رمز جديد إلى بريدك الإلكتروني')
            ->success()
            ->send();
    }

    public static function getRouteName(?string $panel = null): string
    {
        return 'filament.admin.otp-challenge';
    }
}
