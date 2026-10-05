<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\TikTokPurchase;
use App\Services\TikTokService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TikTokPurchaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['tiktok.enabled' => true, 'tiktok.pixel_id' => 'test-pixel',
            'tiktok.access_token' => 'test-token', 'tiktok.test_event_code' => null]);
        Http::preventStrayRequests();
        Schema::create('orders', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->string('status');
            $t->string('payment_status'); $t->decimal('total', 12, 2); $t->timestamps();
        });
        Schema::create('order_items', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('order_id'); $t->unsignedBigInteger('product_id');
            $t->decimal('price', 12, 2); $t->integer('quantity'); $t->timestamps();
        });
        (require database_path('migrations/2026_10_05_000001_create_tiktok_purchases_table.php'))->up();
    }

    private function order(string $payment = 'pending', string $status = 'pending'): Order
    {
        $id = DB::table('orders')->insertGetId(['user_id' => 1, 'status' => $status,
            'payment_status' => $payment, 'total' => 1900, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('order_items')->insert(['order_id' => $id, 'product_id' => 42, 'price' => 1000, 'quantity' => 2]);
        return Order::findOrFail($id);
    }

    private function request(bool $consent = true): Request
    {
        return Request::create('/api/v1/orders', 'POST', ['marketing' => [
            'consent' => $consent, 'ttclid' => 'test_click', 'ttp' => 'test_browser',
            'page_url' => 'https://shop.example/ar/checkout?secret=remove',
            'value' => 999999,
        ]]);
    }

    public function test_no_capture_without_consent_or_when_disabled(): void
    {
        $service = app(TikTokService::class);
        $order = $this->order();
        $service->capture($order, $this->request(false));
        $this->assertSame(0, TikTokPurchase::count());
        config(['tiktok.enabled' => false]);
        $service->capture($order, $this->request());
        $this->assertSame(0, TikTokPurchase::count());
        Http::assertNothingSent();
    }

    public function test_unpaid_and_cancelled_orders_never_send(): void
    {
        Http::fake(['*' => Http::response(['code' => 0])]);
        $service = app(TikTokService::class);
        foreach (['pending', 'processing', 'awaiting_review', 'failed', 'cancelled', 'refunded'] as $status) {
            $order = $this->order($status);
            $service->capture($order, $this->request());
            $this->assertFalse($service->send(TikTokPurchase::where('order_id', $order->id)->firstOrFail()));
        }
        $order = $this->order('paid', 'cancelled');
        $service->capture($order, $this->request());
        $this->assertFalse($service->send(TikTokPurchase::where('order_id', $order->id)->firstOrFail()));
        Http::assertNothingSent();
    }

    public function test_purchase_uses_database_value_and_cannot_resend_after_acceptance(): void
    {
        Http::fake(['*' => Http::response(['code' => 0])]);
        $service = app(TikTokService::class);
        $service->capture($this->order('paid'), $this->request());
        $event = TikTokPurchase::firstOrFail();
        $this->assertTrue($service->send($event));
        $this->assertFalse($service->send($event->fresh()));
        Http::assertSentCount(1);
        Http::assertSent(fn ($r) => $r['data'][0]['event'] === 'Purchase'
            && (float) $r['data'][0]['properties']['value'] === 1900.0
            && $r['data'][0]['properties']['currency'] === 'SAR'
            && $r['data'][0]['properties']['contents'][0]['content_id'] === '42'
            && $r['data'][0]['page']['url'] === 'https://shop.example/ar/checkout');
        $this->assertNull($event->fresh()->context);
    }

    public function test_provider_error_retries_with_identical_event_id_time_and_payload(): void
    {
        Http::fake(['*' => Http::sequence()->push(['code' => 40001])->push(['code' => 0])]);
        $service = app(TikTokService::class);
        $order = $this->order('paid');
        $service->capture($order, $this->request());
        $service->capture($order, $this->request());
        $this->assertSame(1, TikTokPurchase::count());
        $event = TikTokPurchase::firstOrFail();
        $this->assertFalse($service->send($event));
        $payload = $event->fresh()->payload;
        $this->assertNull($event->fresh()->sent_at);
        $this->assertTrue($service->send($event->fresh()));
        $this->assertEquals($payload, Http::recorded()[1][0]['data'][0]);
    }

    public function test_scheduler_waits_for_confirmed_payment(): void
    {
        Http::fake(['*' => Http::response(['code' => 0])]);
        $order = $this->order();
        $service = app(TikTokService::class);
        $service->capture($order, $this->request());
        $this->artisan('tiktok:send-purchases')->assertSuccessful();
        Http::assertNothingSent();
        DB::table('orders')->where('id', $order->id)->update(['payment_status' => 'paid']);
        $service->recordPaid($order->fresh());
        $this->artisan('tiktok:send-purchases')->assertSuccessful();
        $this->artisan('tiktok:send-purchases')->assertSuccessful();
        Http::assertSentCount(1);
    }
}
