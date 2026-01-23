<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromAcceptLanguage
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $acceptLanguage = $request->header('Accept-Language', 'ar');

        // Check if Arabic is requested
        if (str_contains(strtolower($acceptLanguage), 'en')) {
            app()->setLocale('en');
        } else {
            app()->setLocale('ar');
        }

        return $next($request);
    }
}
