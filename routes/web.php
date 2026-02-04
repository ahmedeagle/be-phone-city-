<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
// Route::get('/', function () {
//     return view('welcome');
// });

// Admin Routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    // Order Print Route - Protected by Filament auth (handled in controller)
    Route::get('/orders/{order}/print', [\App\Http\Controllers\Admin\OrderPrintController::class, 'print'])
        ->name('orders.print');

    // Payment Proof Route - Protected by Filament auth (handled in controller)
    Route::get('/payment-transactions/{transaction}/proof', [\App\Http\Controllers\Admin\PaymentProofController::class, 'show'])
        ->name('payment-transactions.proof');
});

// Make sure you have auth routes

Route::prefix('artisan')->group(function () {
    Route::get('/migrate', function () {
        Artisan::call('migrate');
        return 'Migration completed successfully';
    })->name('artisan.migrate');

    Route::get('/seed', function () {
        Artisan::call('db:seed');
        return 'Database seeding completed successfully';
    })->name('artisan.seed');

    Route::get('/seed/{class}', function ($class) {
        Artisan::call('db:seed', ['--class' => $class]);
        return "Seeder {$class} executed successfully";
    })->name('artisan.seed.class');

    Route::get('/import-products', function () {
        $exitCode = Artisan::call('import:products-from-json', [
            'file' => 'product-data.json',
            '--truncate' => true,
            '--no-interaction' => true,
        ]);

        $output = Artisan::output();

        return response()->json([
            'status' => $exitCode === 0 ? 'success' : 'error',
            'exit_code' => $exitCode,
            'output' => $output,
            'message' => $exitCode === 0
                ? 'Products imported successfully!'
                : 'Product import failed!'
        ]);
    })->name('artisan.import.products');

    Route::get('/cache-clear', function () {
        Artisan::call('cache:clear');
        return 'Cache cleared successfully';
    })->name('artisan.cache.clear');

    Route::get('/config-clear', function () {
        Artisan::call('config:clear');
        return 'Config cache cleared successfully';
    })->name('artisan.config.clear');

    Route::get('/migrate-fresh', function () {
        Artisan::call('migrate:fresh');
        return 'Migration fresh completed successfully';
    })->name('artisan.migrate.fresh');

    Route::get('/composer-install', function () {
        $output = shell_exec('composer install');
        return nl2br($output);
    })->name('artisan.composer.install');

    Route::get('/storage-link', function () {
        Artisan::call('storage:link');
        return 'Storage link created successfully';
    })->name('artisan.storage.link');

    Route::get('/routes', function () {
        $routes = collect(Route::getRoutes())->map(function ($route) {
            return [
                'uri'        => $route->uri(),
                'name'       => $route->getName(),
                'method'     => implode('|', $route->methods()),
                'action'     => $route->getActionName(),
            ];
        });

        return response()->json($routes);
    })->name('artisan.route.list');

    Route::get('/clear-all', function () {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('event:clear');
        Artisan::call('optimize:clear');

        // Filament v4 no longer has filament:cache-components
        // It auto-caches components. Just clear optimize is enough.

        return response()->json([
            'status' => 'success',
            'message' => 'All caches cleared successfully for Filament v4!',
            'executed' => [
                'cache:clear', 'config:clear', 'route:clear',
                'view:clear', 'event:clear', 'optimize:clear'
            ]
        ]);
    });

    Route::get('/composer-install-prod', function () {
        // Change to your project directory
        chdir('/home/u920710540/domains/abaadre.com/public_html/city_phone');

        // Run composer install
        $output = [];
        $return_var = 0;
        exec('composer install --optimize-autoloader --no-dev 2>&1', $output, $return_var);

        return response()->json([
            'status' => $return_var === 0 ? 'success' : 'error',
            'output' => implode("\n", $output)
        ]);
    });
});

// Serve static assets from frontend directory
// Serve static assets from frontend directory
Route::get('/assets/{path}', function ($path) {
    $filePath = public_path("frontend/assets/{$path}");

    if (!file_exists($filePath)) {
        abort(404);
    }

    // Determine MIME type based on file extension
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'js' => 'application/javascript',
        'mjs' => 'application/javascript',
        'css' => 'text/css',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
        'json' => 'application/json',
        'html' => 'text/html',
        'ico' => 'image/x-icon',
    ];

    $mimeType = $mimeTypes[$extension] ?? mime_content_type($filePath) ?? 'application/octet-stream';

    return response()->file($filePath, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000', // Cache for 1 year
    ]);
})->where('path', '.*');

// Catch-all route for React app (must be last)
Route::get('/{any}', function () {
    return file_get_contents(public_path('frontend/index.html'));
})->where('any', '.*');
