<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

echo "=== Testing OTO /createOrder directly ===\n\n";

// Get token first
$refreshToken = config('services.oto.key');
$baseUrl = 'https://staging-api.tryoto.com/rest/v2';

echo "Step 1: Getting access token...\n";
$tokenResponse = Http::timeout(30)
    ->post($baseUrl . '/refreshToken', [
        'refresh_token' => $refreshToken,
    ]);

if ($tokenResponse->failed()) {
    echo "❌ Token refresh failed: " . $tokenResponse->body() . "\n";
    exit(1);
}

$accessToken = $tokenResponse->json()['access_token'];
echo "✅ Got access token\n\n";

// Try createOrder
echo "Step 2: Testing POST /createOrder...\n";

$orderPayload = [
    'orderId' => 'TEST-' . time(),
    'reference' => 'TEST-REF-' . time(),
    'createShipment' => true,
    'payment_method' => 'paid',
    'amount' => 100,
    'senderInformation' => [
        'senderAddressName' => 'Test Sender',
        'senderAddress' => 'Test Address',
        'senderCity' => 'Riyadh',
        'senderPhone' => '966501234567',
    ],
    'receiverInformation' => [
        'receiverAddressName' => 'Test Receiver',
        'receiverAddress' => 'Test Address',
        'receiverCity' => 'Jeddah',
        'receiverPhone' => '966509876543',
    ],
];

$response = Http::timeout(30)
    ->withHeaders([
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'Authorization' => "Bearer {$accessToken}",
    ])
    ->post($baseUrl . '/createOrder', $orderPayload);

echo "Status: " . $response->status() . "\n";
echo "Response:\n";
echo json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

if ($response->successful()) {
    echo "\n✅ /createOrder works!\n";
    $data = $response->json();
    echo "Order ID field in response: " . (isset($data['otoId']) ? $data['otoId'] : 'NOT FOUND') . "\n";
} else {
    echo "\n❌ /createOrder failed\n";
}

