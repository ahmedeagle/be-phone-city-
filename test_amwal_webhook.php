<?php

/**
 * Test script for Amwal webhook
 * 
 * This script simulates an Amwal webhook request to test the webhook handler
 * 
 * Usage:
 * php test_amwal_webhook.php <order_id>
 * 
 * Or use curl:
 * curl -X POST http://localhost:8000/api/webhooks/payment/amwal \
 *   -H "Content-Type: application/json" \
 *   -d '{
 *     "payment_link_id": "0bb4543b-69f1-41f3-be1d-a23bf25dd153",
 *     "status": "Paid",
 *     "metadata": {
 *       "order_id": 62,
 *       "order_number": "ORD-WCO2MJCB-20260124"
 *     }
 *   }'
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Log;

echo "=== Amwal Webhook Test ===\n\n";

// Get order ID from command line or use default
$orderId = $argv[1] ?? 62;

$order = Order::find($orderId);

if (!$order) {
    echo "❌ Order #{$orderId} not found\n";
    exit(1);
}

$transaction = $order->getLatestPaymentTransaction();

if (!$transaction) {
    echo "❌ No payment transaction found for this order\n";
    exit(1);
}

echo "📦 Order: #{$order->order_number} (ID: {$order->id})\n";
echo "💳 Transaction ID: {$transaction->transaction_id}\n";
echo "📊 Current Payment Status: {$order->payment_status}\n";
echo "📋 Current Transaction Status: {$transaction->status}\n\n";

// Simulate webhook payload
$webhookPayload = [
    'payment_link_id' => $transaction->transaction_id,
    'status' => 'Paid', // or 'success' from transaction
    'metadata' => [
        'order_id' => $order->id,
        'order_number' => $order->order_number,
        'user_id' => $order->user_id,
    ],
    'amount' => (string) $transaction->amount,
    'currency' => $transaction->currency,
    'created_at' => now()->toIso8601String(),
];

echo "📨 Simulating webhook payload:\n";
echo json_encode($webhookPayload, JSON_PRETTY_PRINT) . "\n\n";

echo "🔄 Processing webhook...\n";

try {
    $paymentService = app(PaymentService::class);
    $result = $paymentService->handleWebhook('amwal', $webhookPayload);
    
    echo "\n✅ Webhook processed\n";
    echo "   Success: " . ($result['success'] ? 'Yes' : 'No') . "\n";
    echo "   Order ID: " . ($result['order_id'] ?? 'N/A') . "\n";
    echo "   Status: " . ($result['status'] ?? 'N/A') . "\n";
    if (isset($result['message'])) {
        echo "   Message: " . $result['message'] . "\n";
    }
    
    // Refresh order and transaction
    $order->refresh();
    $transaction->refresh();
    
    echo "\n📊 Updated Status:\n";
    echo "   Payment Status: {$order->payment_status}\n";
    echo "   Transaction Status: {$transaction->status}\n";
    echo "   Order Status: {$order->status}\n";
    
    if ($order->payment_status === 'paid') {
        echo "\n✅ SUCCESS! Order is now paid!\n";
    } else {
        echo "\n⚠️  Order payment status is still: {$order->payment_status}\n";
        echo "   Check logs for details: storage/logs/laravel.log\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ Error processing webhook: {$e->getMessage()}\n";
    echo "   Trace: {$e->getTraceAsString()}\n";
    exit(1);
}

echo "\n=== Test Complete ===\n";
