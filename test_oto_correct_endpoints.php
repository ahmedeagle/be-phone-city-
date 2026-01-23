<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use App\Services\Shipping\OtoShippingService;

echo "=== Testing OTO with CORRECT Endpoints ===\n\n";
echo "Using endpoints from OTO official docs:\n";
echo "- POST /createOrder\n";
echo "- POST /createShipment\n\n";

// Find order
$order = Order::with(['location.city', 'items.product', 'items.productOption', 'user'])
    ->find(26);

if (!$order) {
    echo "❌ Order #26 not found\n";
    exit(1);
}

echo "✅ Testing with order: {$order->order_number}\n\n";

try {
    $service = app(OtoShippingService::class);

    echo "📦 Step 1: Creating order in OTO...\n";
    echo "📦 Step 2: Creating shipment...\n\n";

    $notes = "Test with correct endpoints - " . date('H:i:s');
    $shipment = $service->createShipment($order, $notes);

    echo "🎉 SUCCESS! Full automation working!\n\n";
    echo "✅ OTO Order ID: {$order->fresh()->oto_order_id}\n";
    echo "✅ Tracking Number: {$shipment->trackingNumber}\n";
    echo "✅ Tracking URL: {$shipment->trackingUrl}\n";
    echo "✅ Status: {$shipment->status}\n";

    if ($shipment->eta) {
        echo "✅ ETA: {$shipment->eta}\n";
    }

    echo "\n🚀 Full API integration is now working!\n";
    echo "🎯 One-click shipping achieved!\n";

} catch (\App\Services\Shipping\Oto\Exceptions\OtoValidationException $e) {
    echo "❌ Validation Error: {$e->getMessage()}\n";
    exit(1);
} catch (\App\Services\Shipping\Oto\Exceptions\OtoApiException $e) {
    echo "❌ API Error: {$e->getMessage()}\n";
    echo "\n📋 Check logs: storage/logs/laravel.log\n";
    exit(1);
} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    echo "\n📋 Check logs: storage/logs/laravel.log\n";
    exit(1);
}
