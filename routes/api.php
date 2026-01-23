<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AboutController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CertificateController;
use App\Http\Controllers\Api\V1\CityController;
use App\Http\Controllers\Api\V1\ContactRequestController;
use App\Http\Controllers\Api\V1\CustomerOpinionController;
use App\Http\Controllers\Api\V1\DiscountController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PointController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\OfferController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\StoreFeatureController;
use App\Http\Controllers\Api\V1\SliderController;
use App\Http\Controllers\Api\V1\HomePageController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\BlogController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\TicketController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->namespace('App\Http\Controllers\Api\V1')->group(function () {

    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('verify-code', [AuthController::class, 'verifyCode']);
        Route::post('resend-code', [AuthController::class, 'resendCode']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
        Route::post('social/{provider}', [AuthController::class, 'socialLogin']);

        // Protected auth routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
        });
    });

    // Public routes
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/trademarks', [CategoryController::class, 'trademarks']);
    Route::get('categories/{category}', [CategoryController::class, 'show']);
    Route::get('categories/{category}/products', [CategoryController::class, 'products']);

    Route::get('cities', [CityController::class, 'index']);
    Route::get('cities/list', [CityController::class, 'activeList']);

    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/new-arrivals', [ProductController::class, 'newArrivals']);
    Route::get('products/featured', [ProductController::class, 'featured']);
    Route::get('products/{product}', [ProductController::class, 'show']);

    Route::get('offers', [OfferController::class, 'index']);
    Route::get('/offers/home', [OfferController::class, 'homeOffers']);
    Route::get('offers/{offer}', [OfferController::class, 'show']);

    Route::get('pages', [PageController::class, 'index']);
    Route::get('pages/{identifier}', [PageController::class, 'show']);

    Route::get('services', [ServiceController::class, 'index']);
    Route::get('services/{id}', [ServiceController::class, 'show']);

    Route::get('store-features', [StoreFeatureController::class, 'index']);
    Route::get('store-features/{id}', [StoreFeatureController::class, 'show']);

    Route::get('certificates', [CertificateController::class, 'index']);
    Route::get('certificates/{id}', [CertificateController::class, 'show']);

    Route::get('customer-opinions', [CustomerOpinionController::class, 'index']);
    Route::get('customer-opinions/{id}', [CustomerOpinionController::class, 'show']);

    Route::get('about', [AboutController::class, 'show']);

    Route::get('sliders', [SliderController::class, 'index']);
    Route::get('sliders/{id}', [SliderController::class, 'show']);

    Route::get('home-page', [HomePageController::class, 'show']);

    // Settings routes (specific routes must come before dynamic {key} route)
    Route::get('settings', [SettingController::class, 'index']);
    Route::get('settings/website/info', [SettingController::class, 'websiteInfo']);
    Route::get('settings/shipping/tax', [SettingController::class, 'shippingTax']);
    Route::get('settings/points', [SettingController::class, 'points']);
    Route::get('settings/bank/details', [SettingController::class, 'bankDetails']);
    Route::get('settings/products/sections', [SettingController::class, 'productSections']);
    Route::get('settings/{key}', [SettingController::class, 'show']);

    Route::get('blogs', [BlogController::class, 'index']);
    Route::get('blogs/{identifier}', [BlogController::class, 'show']);

    Route::get('comments', [CommentController::class, 'index']);
    Route::get('comments/{id}', [CommentController::class, 'show']);

    Route::post('contact', [ContactRequestController::class, 'store']);

    // Tickets routes (public - guest tickets)
    Route::post('tickets', [TicketController::class, 'store']);

    // Payment routes (public)
    Route::get('payment/bank-account-details', [PaymentController::class, 'bankAccountDetails'])
        ->name('payment.bankAccountDetails');

    // Reviews routes (public to view, protected to create/update/delete)
    Route::get('reviews', [ReviewController::class, 'index']);
    Route::get('reviews/{id}', [ReviewController::class, 'show']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Cart routes
        Route::prefix('cart')->group(function () {
            Route::get('/', [CartController::class, 'index']);
            Route::post('/', [CartController::class, 'store']); // Add single item
            Route::post('multiple', [CartController::class, 'storeMultiple']); // Add multiple items
            Route::put('/{id}', [CartController::class, 'update']);
            Route::delete('/product', [CartController::class, 'removeByProduct']); // Delete all items for a product
            Route::delete('/{id}', [CartController::class, 'destroy']); // Delete by cart ID
            Route::delete('/', [CartController::class, 'clear']);
        });

        // Favorites routes
        Route::prefix('favorites')->group(function () {
            Route::get('/', [FavoriteController::class, 'index']);
            Route::post('/', [FavoriteController::class, 'store']); // Add single favorite
            Route::post('multiple', [FavoriteController::class, 'storeMultiple']); // Add multiple favorites
            Route::delete('/{id}', [FavoriteController::class, 'destroy']);
            Route::post('toggle', [FavoriteController::class, 'toggle']);
            Route::delete('/', [FavoriteController::class, 'clear']);
            Route::get('check/{productId}', [FavoriteController::class, 'check']);
        });

        // Locations routes
        Route::prefix('locations')->group(function () {
            Route::get('/', [LocationController::class, 'index']);
            Route::post('/', [LocationController::class, 'store']);
            Route::get('/{id}', [LocationController::class, 'show']);
            Route::put('/{id}', [LocationController::class, 'update']);
            Route::delete('/{id}', [LocationController::class, 'destroy']);
        });

        // Profile routes
        Route::prefix('profile')->group(function () {
            Route::get('/', [ProfileController::class, 'show']);
            Route::put('/', [ProfileController::class, 'update']);
        });

        // Reviews routes (create, update, delete)
        Route::prefix('reviews')->group(function () {
            Route::post('/', [ReviewController::class, 'store']);
            Route::put('/{id}', [ReviewController::class, 'update']);
            Route::delete('/{id}', [ReviewController::class, 'destroy']);
            Route::get('/my-reviews', [ReviewController::class, 'myReviews']);
        });

        // Comments routes (create, update, delete)
        Route::prefix('comments')->group(function () {
            Route::post('/', [CommentController::class, 'store']);
            Route::put('/{id}', [CommentController::class, 'update']);
            Route::delete('/{id}', [CommentController::class, 'destroy']);
            Route::get('/my-comments', [CommentController::class, 'myComments']);
        });

        // Tickets routes (authenticated users only)
        Route::prefix('tickets')->group(function () {
            Route::get('/', [TicketController::class, 'index']); // Returns user's own tickets
            Route::get('/{id}', [TicketController::class, 'show']); // Returns user's own ticket
            Route::put('/{id}', [TicketController::class, 'update']); // User can update their own ticket
        });

        // Discounts routes
        Route::prefix('discounts')->group(function () {
            Route::get('/', [DiscountController::class, 'index']);
            Route::post('/check-code', [DiscountController::class, 'checkCode']);
        });

        // Orders routes
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'index']);
            Route::post('/preview', [OrderController::class, 'preview']);
            Route::post('/', [OrderController::class, 'store']);
            Route::get('/{id}', [OrderController::class, 'show']);
        });

        // Invoices routes
        Route::prefix('invoices')->group(function () {
            Route::get('/', [InvoiceController::class, 'index']);
            Route::get('/{id}', [InvoiceController::class, 'show']);
        });

        // Points routes
        Route::prefix('points')->group(function () {
            Route::get('/', [PointController::class, 'index']);
            Route::get('/summary', [PointController::class, 'summary']);
            Route::get('/{id}', [PointController::class, 'show']);
        });

        // Notifications routes
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
            Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
            Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
            Route::delete('/{id}', [NotificationController::class, 'destroy']);
            Route::delete('/clear-all', [NotificationController::class, 'clearAll']);
        });

        // Payment routes (authenticated)
        Route::prefix('orders/{order}/payment')->group(function () {
            Route::post('/retry', [PaymentController::class, 'retry'])
                ->name('orders.payment.retry');
            Route::get('/status', [PaymentController::class, 'checkStatus'])
                ->name('orders.payment.status');
            Route::post('/upload-proof', [PaymentController::class, 'uploadProof'])
                ->name('orders.payment.uploadProof');
        });

    });

    // Payment callback (no auth required - gateway redirects here)
    Route::match(['get', 'post'], 'payment/callback/{order}', [PaymentController::class, 'callback'])
        ->name('payment.callback');
});

// Payment webhooks (outside v1 prefix, no auth required - called by gateway servers)
Route::post('webhooks/payment/{gateway}', [PaymentController::class, 'webhook'])
    ->name('payment.webhook');

// OTO Shipping webhooks (outside v1 prefix, no auth required - called by OTO servers)
Route::post('webhooks/oto/shipment', [\App\Http\Controllers\Webhooks\OtoShipmentWebhookController::class, 'handle'])
    ->name('webhooks.oto.shipment');
