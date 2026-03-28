<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class OtoDiagnosticCommand extends Command
{
    protected $signature = 'oto:diagnose';
    protected $description = 'Diagnose OTO API connection and configuration issues';

    public function handle(): int
    {
        $this->info('🔍 OTO API Diagnostic Report');
        $this->info('============================');
        $this->newLine();

        // 1. Check ENV variables
        $this->info('1️⃣  Environment Variables:');
        $key = config('services.oto.key');
        $secret = config('services.oto.secret');
        $env = config('services.oto.environment');
        $baseUrl = config('services.oto.urls.base');

        $this->table(['Variable', 'Status', 'Value'], [
            ['OTO_API_KEY', $key ? '✅ Set' : '❌ MISSING', $key ? substr($key, 0, 10) . '...' : 'NOT SET'],
            ['OTO_API_SECRET', $secret ? '✅ Set' : '❌ MISSING', $secret ? substr($secret, 0, 10) . '...' : 'NOT SET'],
            ['OTO_ENVIRONMENT', '✅ ' . $env, $env],
            ['OTO_API_BASE_URL', $baseUrl ? '✅ Set' : '⚠️ Using default', $baseUrl ?: 'https://api.tryoto.com/rest/v2'],
        ]);

        if (!$key) {
            $this->error('❌ OTO_API_KEY is not set! Add it to your .env file.');
            return self::FAILURE;
        }

        // 2. Determine actual base URL
        $actualUrl = $baseUrl;
        if (!$actualUrl) {
            $actualUrl = match ($env) {
                'production' => config('services.oto.urls.production'),
                'sandbox', 'staging' => config('services.oto.urls.sandbox'),
                default => config('services.oto.urls.production'),
            };
        }
        $this->info("📡 Using base URL: {$actualUrl}");
        $this->newLine();

        // 3. Test DNS resolution
        $this->info('2️⃣  DNS Resolution:');
        $parsed = parse_url($actualUrl);
        $host = $parsed['host'] ?? '';
        $ip = gethostbyname($host);
        if ($ip === $host) {
            $this->error("❌ Cannot resolve {$host} — DNS FAILURE");
            return self::FAILURE;
        }
        $this->info("✅ {$host} resolves to {$ip}");
        $this->newLine();

        // 4. Test token refresh
        $this->info('3️⃣  Token Refresh:');
        $tokenEndpoint = $actualUrl . config('services.oto.endpoints.refresh_token', '/refreshToken');
        $this->info("   POST {$tokenEndpoint}");

        try {
            $response = Http::timeout(15)->post($tokenEndpoint, [
                'refresh_token' => $key,
            ]);

            $status = $response->status();
            $body = $response->json();

            if ($response->successful() && isset($body['access_token'])) {
                $this->info("✅ Token obtained! Expires in: " . ($body['expires_in'] ?? '?') . 's');
                $token = $body['access_token'];
            } else {
                $this->error("❌ Token failed! HTTP {$status}");
                $this->error("   Response: " . json_encode($body, JSON_UNESCAPED_UNICODE));
                return self::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("❌ Connection failed: " . $e->getMessage());
            return self::FAILURE;
        }
        $this->newLine();

        // 5. Test orderStatus endpoint
        $this->info('4️⃣  API Endpoint Test (orderStatus):');
        $statusEndpoint = $actualUrl . config('services.oto.endpoints.order_status', '/orderStatus');
        $this->info("   POST {$statusEndpoint}");

        try {
            $response = Http::timeout(15)
                ->withHeaders(['Authorization' => 'Bearer ' . $token])
                ->post($statusEndpoint, ['OrderId' => 'TEST-DIAG-000']);

            $status = $response->status();
            // Any response means the endpoint is reachable
            if ($status === 200 || $status === 400 || $status === 404) {
                $this->info("✅ API endpoint reachable (HTTP {$status})");
            } else {
                $this->warn("⚠️  HTTP {$status} — " . $response->body());
            }
        } catch (\Exception $e) {
            $this->error("❌ API call failed: " . $e->getMessage());
        }
        $this->newLine();

        // 6. Check cached token
        $this->info('5️⃣  Cache Status:');
        $cacheKey = 'oto_access_token_' . md5($key . $actualUrl);
        $cachedToken = Cache::get($cacheKey);
        $this->info($cachedToken ? '✅ Token is cached' : '⚠️  No cached token (will refresh on next API call)');
        $this->newLine();

        // 7. Check orders pending sync
        $this->info('6️⃣  Orders Pending OTO Sync:');
        $pendingSync = \App\Models\Order::query()
            ->where('shipping_provider', 'OTO')
            ->whereNotNull('oto_order_id')
            ->whereNotIn('status', ['delivered', 'completed', 'cancelled'])
            ->count();
        $this->info("   {$pendingSync} orders pending sync");

        $noOtoId = \App\Models\Order::query()
            ->where('status', 'processing')
            ->where('delivery_method', 'home_delivery')
            ->whereNull('oto_order_id')
            ->count();
        $this->info("   {$noOtoId} processing orders WITHOUT OTO ID (need shipment creation)");

        $storePickup = \App\Models\Order::query()
            ->where('delivery_method', 'store_pickup')
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->count();
        $this->info("   {$storePickup} active store pickup orders");

        $this->newLine();
        $this->info('✅ Diagnostic complete!');

        return self::SUCCESS;
    }
}
