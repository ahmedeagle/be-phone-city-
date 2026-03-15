<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema; // ⬅ مهم جداً عشان defaultStringLength
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Cache;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // حل مشكلة "Specified key was too long; max key length is 1000 bytes"
        Schema::defaultStringLength(191);

        // Register Observers
        \App\Models\Order::observe(\App\Observers\OrderObserver::class);
        \App\Models\Offer::observe(\App\Observers\OfferObserver::class);
        \App\Models\Ticket::observe(\App\Observers\TicketObserver::class);
        \App\Models\Review::observe(\App\Observers\ReviewObserver::class);
        \App\Models\ContactRequest::observe(\App\Observers\ContactRequestObserver::class);

        // Clear OTP verified cache when admin logs out (forces re-verification on next login)
        Event::listen(Logout::class, function (Logout $event) {
            if ($event->guard === 'admin' && $event->user) {
                Cache::forget('admin_otp_verified_' . $event->user->id);
            }
        });

        // الماكروز اللي أنت عاملها تفضل زي ما هي 👇
        Response::macro('success', function ($message, $data = [], $status = 200, $pagination = []) {
            $response = [
                'status'   => true,
                'message'  => __($message),
                'data'     => $data,
            ];

            if ($pagination) {
                $response['pagination'] = $pagination;
            }

            return response()->json($response, $status);
        });

        Response::macro('error', function ($message = '', $errors = '', $status = 400) {
            return response()->json([
                'status'  => false,
                'message' => __($message),
                'errors'  => $errors,
            ], $status);
        });
    }
}








// <?php

// namespace App\Providers;

// use Illuminate\Support\Facades\Response;
// use Illuminate\Support\ServiceProvider;

// class AppServiceProvider extends ServiceProvider
// {
    /**
     * Register any application services.
     */
    // public function register(): void
    // {

    // }

    /**
     * Bootstrap any application services.
     */
//     public function boot(): void
//     {
//         Response::macro('success', function ($message, $data = [], $status = 200, $pagination = []) {
//             $response = [
//                 'status' => true,
//                 'message' => __($message),
//                 'data' => $data,
//             ];

//             if ($pagination) {
//                 $response['pagination'] = $pagination;
//             }

//             return response()->json($response, $status);
//         });

//         Response::macro('error', function ($message = '', $errors = '', $status = 400) {
//             return response()->json([
//                 'status' => false,
//                 'message' => __($message),
//                 'errors' => $errors,
//             ], $status);
//         });
//     }
// }
