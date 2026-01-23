<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use Illuminate\Support\Facades\Log;

echo "=== Debug OTO Integration ===\n\n";

$order = Order::with(['location.city', 'items.product', 'items.productOption', 'user'])->find(25);

if (!$order) {
    die("Order not found\n");
}

echo "Order loaded\n";

try {
    $client = app(\App\Services\Shipping\Oto\OtoHttpClient::class);
    echo "Client created\n";
    
    // Build payload manually
    $payload = [
        'orderId' => $order->order_number,
        'createShipment' => true,
        'payment_method' => 'prepaid',
        'amount' => (float) $order->total,
        'senderInformation' => [
            'senderAddressName' => 'City Phones',
            'senderAddress' => 'test address',
            'senderCity' => 'test city',
            'senderPhone' => '99999999',
            'senderEmail' => 'test@test.com',
        ],
        'receiverInformation' => [
            'receiverAddressName' => 'Test Receiver',
            'receiverAddress' => '77385 Brown Ways',
            'receiverCity' => 'Madinah',
            'receiverPhone' => '820.699.0715',
            'receiverEmail' => 'test@example.com',
        ],
    ];
    
    echo "Payload built\n";
    echo "Calling createOrder...\n";
    
    $response = $client->createOrder($payload);
    
    echo "✅ createOrder returned!\n";
    print_r($response);
    
} catch (\App\Services\Shipping\Oto\Exceptions\OtoApiException $e) {
    echo "❌ OtoApiException: " . $e->getMessage() . "\n";
    echo "Status: " . $e->getCode() . "\n";
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Class: " . get_class($e) . "\n";
    echo "Code: " . $e->getCode() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

