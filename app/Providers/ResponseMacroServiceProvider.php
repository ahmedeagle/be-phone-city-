<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;

class ResponseMacroServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Response::macro('success', function ($message, $data = [], $status = 200, $pagination = []) {
            $response = [
                'status' => true,
                'message' => __($message),
                'data' => $data,
            ];

            if ($pagination) {
                $response['pagination'] = $pagination;
            }

            return response()->json($response, $status);
        });


        Response::macro('error', function ($message = 'Error', $data = null, $code = 400) {
            return response()->json([
                'status' => false,
                'message' => $message,
                'errors' => $data,
            ], $code);
        });
    }
}
