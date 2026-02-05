<?php

/**
 * Unified Test script for Payment Webhooks (Amwal, Moyasar, Tamara, Tabby)
 *
 * Usage:
 * php test_payment_webhooks.php <gateway> <order_id>
 *
 * Examples:
 * php test_payment_webhooks.php amwal 76
 * php test_payment_webhooks.php moyasar 76
 * php test_payment_webhooks.php tamara 76
 * php test_payment_webhooks.php tabby 76
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Log;

echo "=== Payment Webhook Unified Test ===\n\n";

$gateway = $argv[1] ?? null;
$orderId = $argv[2] ?? null;

$supportedGateways = ['amwal', 'moyasar', 'tamara', 'tabby'];

if (!$gateway || !in_array($gateway, $supportedGateways)) {
    echo "❌ Please specify a valid gateway: " . implode(', ', $supportedGateways) . "\n";
    echo "   Usage: php test_payment_webhooks.php <gateway> <order_id>\n";
    exit(1);
}

if (!$orderId) {
    echo "❌ Please specify an order ID\n";
    exit(1);
}

$order = Order::find($orderId);

if (!$order) {
    echo "❌ Order #{$orderId} not found\n";
    exit(1);
}

$transaction = $order->getLatestPaymentTransaction();

if (!$transaction) {
    // Create a dummy transaction if none exists for testing purposes
    echo "⚠️ No payment transaction found for this order. Creating a dummy one for gateway '{$gateway}'...\n";
    $transaction = PaymentTransaction::create([
        'order_id' => $order->id,
        'payment_method_id' => $order->payment_method_id ?? 1,
        'gateway' => $gateway,
        'amount' => $order->total,
        'currency' => $order->currency ?? 'SAR',
        'status' => 'pending',
        'transaction_id' => 'test_' . $gateway . '_' . time(),
    ]);
}

echo "📦 Order: #{$order->order_number} (ID: {$order->id})\n";
echo "💳 Gateway: {$gateway}\n";
echo "💳 Transaction ID: {$transaction->transaction_id}\n";
echo "📊 Current Payment Status: {$order->payment_status}\n";
echo "📋 Current Transaction Status: {$transaction->status}\n\n";

// Prepare payload based on gateway
$webhookPayload = [];

switch ($gateway) {
    case 'amwal':
        $webhookPayload = [
            'payment_link_id' => $transaction->transaction_id,
            'status' => 'Paid',
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $order->user_id,
            ],
            'amount' => (string) $transaction->amount,
            'currency' => $transaction->currency,
        ];
        break;

    case 'moyasar':
        $webhookPayload = [
            'type' => 'payment_paid',
            'data' => [
                'id' => $transaction->transaction_id,
                'status' => 'paid',
                'amount' => (int)($transaction->amount * 100),
                'currency' => $transaction->currency,
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ]
            ]
        ];
        break;

    case 'tamara':
        $webhookPayload = [
            'order_id' => $transaction->transaction_id,
            'order_reference_id' => $order->order_number,
            'status' => 'approved',
            'amount' => [
                'amount' => (float)$transaction->amount,
                'currency' => $transaction->currency,
            ]
        ];
        break;

    case 'tabby':
        $webhookPayload = [
            'id' => $transaction->transaction_id,
            'status' => 'authorized',
            'order' => [
                'reference_id' => $order->order_number,
            ],
            'amount' => number_format($transaction->amount, 2, '.', ''),
            'currency' => $transaction->currency,
        ];
        break;
}

echo "📨 Simulating webhook payload for {$gateway}:\n";
echo json_encode($webhookPayload, JSON_PRETTY_PRINT) . "\n\n";

echo "🔄 Processing webhook through PaymentService...\n";

try {
    $paymentService = app(PaymentService::class);
    $result = $paymentService->handleWebhook($gateway, $webhookPayload);

    echo "\n✅ Webhook handled by PaymentService\n";
    echo "   Success: " . ($result['success'] ? 'Yes' : 'No') . "\n";
    echo "   Order ID: " . ($result['order_id'] ?? 'N/A') . "\n";
    echo "   Status: " . ($result['status'] ?? 'N/A') . "\n";
    if (isset($result['message'])) {
        echo "   Message: " . $result['message'] . "\n";
    }

    // Refresh order and transaction
    $order->refresh();
    $transaction->refresh();

    echo "\n📊 Updated Status in Database:\n";
    echo "   Payment Status: {$order->payment_status}\n";
    echo "   Transaction Status: {$transaction->status}\n";
    echo "   Order Status: {$order->status}\n";

    if ($order->payment_status === 'paid') {
        echo "\n✅ SUCCESS! Order is now paid!\n";
    } else {
        echo "\n⚠️  Order payment status is: {$order->payment_status}\n";
        echo "   It might be 'processing' or 'awaiting_review' depending on the gateway logic.\n";
    }

} catch (\Exception $e) {
    echo "\n❌ Error processing webhook: {$e->getMessage()}\n";
    // echo "   Trace: {$e->getTraceAsString()}\n";
    exit(1);
}

echo "\n=== Test Complete ===\n";
