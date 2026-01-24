<?php

/**
 * Test script for Amwal payment callback and webhook
 * 
 * This script helps test the Amwal payment callback and webhook flow
 * 
 * Usage:
 * 1. Create an order with Amwal payment
 * 2. Complete payment on Amwal
 * 3. Check logs to see if callback/webhook processed correctly
 * 
 * To test callback manually:
 * php artisan tinker
 * >>> $order = \App\Models\Order::find(60);
 * >>> $transaction = $order->getLatestPaymentTransaction();
 * >>> $gateway = \App\Services\PaymentGateways\PaymentGatewayFactory::make('amwal');
 * >>> $status = $gateway->getPaymentStatus($transaction->transaction_id);
 * >>> print_r($status);
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\PaymentGateways\PaymentGatewayFactory;
use Illuminate\Support\Facades\Log;

echo "=== Amwal Payment Callback/Webhook Test ===\n\n";

// Get order ID from command line or use default
$orderId = $argv[1] ?? 60;

$order = Order::find($orderId);

if (!$order) {
    echo "❌ Order #{$orderId} not found\n";
    exit(1);
}

echo "📦 Order: #{$order->order_number} (ID: {$order->id})\n";
echo "💰 Total: {$order->total} {$order->currency}\n";
echo "💳 Payment Method: {$order->paymentMethod?->name} ({$order->paymentMethod?->gateway})\n";
echo "📊 Payment Status: {$order->payment_status}\n";
echo "📋 Order Status: {$order->status}\n\n";

$transaction = $order->getLatestPaymentTransaction();

if (!$transaction) {
    echo "❌ No payment transaction found for this order\n";
    exit(1);
}

echo "💳 Transaction Details:\n";
echo "   ID: {$transaction->id}\n";
echo "   Transaction ID: {$transaction->transaction_id}\n";
echo "   Gateway: {$transaction->gateway}\n";
echo "   Status: {$transaction->status}\n";
echo "   Amount: {$transaction->amount} {$transaction->currency}\n";
echo "   Created: {$transaction->created_at}\n\n";

if ($transaction->gateway !== 'amwal') {
    echo "⚠️  Warning: This transaction is not using Amwal gateway\n";
    echo "   Current gateway: {$transaction->gateway}\n\n";
}

// Test payment status check
echo "🔍 Checking payment status from Amwal API...\n";

try {
    $gateway = PaymentGatewayFactory::make('amwal');
    
    if (!$gateway->isEnabled()) {
        echo "❌ Amwal gateway is disabled\n";
        echo "   Check AMWAL_ENABLED in .env\n";
        exit(1);
    }
    
    $statusResponse = $gateway->getPaymentStatus($transaction->transaction_id);
    
    if ($statusResponse['success']) {
        echo "✅ Status check successful\n";
        echo "   Status: {$statusResponse['status']}\n";
        if (isset($statusResponse['data']['status'])) {
            echo "   Amwal Status: {$statusResponse['data']['status']}\n";
        }
        if (isset($statusResponse['data']['payment_link_id'])) {
            echo "   Payment Link ID: {$statusResponse['data']['payment_link_id']}\n";
        }
    } else {
        echo "❌ Status check failed\n";
        echo "   Error: {$statusResponse['message']}\n";
    }
    
    echo "\n";
    
} catch (\Exception $e) {
    echo "❌ Error checking payment status: {$e->getMessage()}\n\n";
}

// Show callback URL
echo "🔗 Callback URLs:\n";
$callbackUrl = route('payment.callback', ['order' => $order->id], false);
$webhookUrl = route('payment.webhook', ['gateway' => 'amwal'], false);
$baseUrl = config('app.url');

echo "   User Callback: {$baseUrl}{$callbackUrl}\n";
echo "   Webhook URL: {$baseUrl}{$webhookUrl}\n\n";

// Show what should happen
echo "📝 Expected Flow:\n";
echo "   1. User completes payment on Amwal\n";
echo "   2. Amwal redirects to: {$baseUrl}{$callbackUrl}\n";
echo "      → PaymentController::callback() processes the redirect\n";
echo "      → Gets transaction ID from order if not in request\n";
echo "      → Calls getPaymentStatus() to check payment status\n";
echo "      → Updates transaction and order status\n";
echo "   3. Amwal sends webhook to: {$baseUrl}{$webhookUrl}\n";
echo "      → PaymentController::webhook() processes the webhook\n";
echo "      → AmwalGateway::handleWebhook() extracts order info\n";
echo "      → PaymentService::handleWebhook() updates transaction and order\n\n";

// Check current order status
echo "📊 Current Order State:\n";
echo "   Payment Status: {$order->payment_status}\n";
echo "   Order Status: {$order->status}\n";
echo "   Transaction Status: {$transaction->status}\n\n";

if ($order->payment_status === 'paid') {
    echo "✅ Order is already paid!\n";
} elseif ($order->payment_status === 'pending' || $order->payment_status === 'processing') {
    echo "⏳ Order payment is pending. After payment:\n";
    echo "   - Check logs: storage/logs/laravel.log\n";
    echo "   - Look for: 'Payment callback received' or 'Payment webhook received'\n";
    echo "   - Verify order status updated to 'paid'\n";
}

echo "\n=== Test Complete ===\n";
