<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema; // ⬅ مهم جداً عشان defaultStringLength
use Illuminate\Support\ServiceProvider;

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
        \App\Models\Ticket::observe(\App\Observers\TicketObserver::class);
        \App\Models\Review::observe(\App\Observers\ReviewObserver::class);
        \App\Models\ContactRequest::observe(\App\Observers\ContactRequestObserver::class);

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
