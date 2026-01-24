<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;

$orderId = $argv[1] ?? 62;

$order = Order::find($orderId);

if (!$order) {
    echo "Order #{$orderId} not found\n";
    exit(1);
}

$transaction = $order->getLatestPaymentTransaction();

echo "Order #{$order->order_number} (ID: {$order->id})\n";
echo "Payment Status: {$order->payment_status}\n";
echo "Order Status: {$order->status}\n";

if ($transaction) {
    echo "Transaction Status: {$transaction->status}\n";
    echo "Transaction ID: {$transaction->transaction_id}\n";
} else {
    echo "No transaction found\n";
}
