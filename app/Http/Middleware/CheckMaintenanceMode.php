<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): HttpResponse
    {
        // Check if maintenance mode is enabled
        $maintenanceMode = Setting::get('maintenance_mode', false);

        if ($maintenanceMode) {
            // Allow access to settings endpoints (to check status and toggle maintenance mode)
            $allowedRoutes = [
                'api/v1/settings',
                'api/v1/settings/maintenance/toggle',
                'api/v1/settings/website/info',
                'api/v1/settings/shipping/tax',
                'api/v1/settings/points',
                'api/v1/settings/bank/details',
                'api/v1/settings/products/sections',
            ];

            $currentPath = $request->path();
            $isAllowed = false;

            // Check if current path matches any allowed route
            foreach ($allowedRoutes as $route) {
                // Exact match or starts with the route (for dynamic routes like settings/{key})
                if ($currentPath === $route || str_starts_with($currentPath, $route . '/')) {
                    $isAllowed = true;
                    break;
                }
            }

            // Also allow settings/{key} routes
            if (!$isAllowed && preg_match('#^api/v1/settings/[^/]+$#', $currentPath)) {
                $isAllowed = true;
            }

            // Block all other routes when maintenance mode is enabled
            if (!$isAllowed) {
                return Response::error(
                    __('The store is currently under maintenance. Please try again later.'),
                    [
                        'maintenance_mode' => true,
                    ],
                    503 // Service Unavailable
                );
            }
        }

        return $next($request);
    }
}
