<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test OTO webhook endpoint
 */
class OtoWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();

        $this->order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_SHIPPED,
            'tracking_number' => 'TRK123456',
            'tracking_status' => 'shipped',
            'shipping_reference' => 'REF123456',
            'shipping_provider' => 'OTO',
        ]);
    }

    /**
     * Test webhook with valid signature
     */
    public function test_webhook_accepts_valid_signature(): void
    {
        config(['services.oto.webhook.secret' => 'test-secret']);
        config(['services.oto.webhook.strict_verification' => true]);

        $payload = [
            'tracking_number' => $this->order->tracking_number,
            'status' => 'out_for_delivery',
            'updated_at' => now()->toDateTimeString(),
        ];

        $signature = hash_hmac('sha256', json_encode($payload), 'test-secret');

        $response = $this->postJson('/api/webhooks/oto/shipment', $payload, [
            'X-OTO-Signature' => $signature,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify order was updated
        $this->order->refresh();
        $this->assertEquals('out_for_delivery', $this->order->tracking_status);
        $this->assertEquals(Order::STATUS_IN_PROGRESS, $this->order->status);
    }

    /**
     * Test webhook rejects invalid signature
     */
    public function test_webhook_rejects_invalid_signature(): void
    {
        config(['services.oto.webhook.secret' => 'test-secret']);
        config(['services.oto.webhook.strict_verification' => true]);

        $payload = [
            'tracking_number' => $this->order->tracking_number,
            'status' => 'delivered',
        ];

        $response = $this->postJson('/api/webhooks/oto/shipment', $payload, [
            'X-OTO-Signature' => 'invalid-signature',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['success' => false]);
    }

    /**
     * Test webhook without strict verification
     */
    public function test_webhook_without_strict_verification(): void
    {
        config(['services.oto.webhook.strict_verification' => false]);

        $payload = [
            'tracking_number' => $this->order->tracking_number,
            'status' => 'delivered',
            'updated_at' => now()->toDateTimeString(),
        ];

        $response = $this->postJson('/api/webhooks/oto/shipment', $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify order was updated
        $this->order->refresh();
        $this->assertEquals('delivered', $this->order->tracking_status);
        $this->assertEquals(Order::STATUS_DELIVERED, $this->order->status);
    }

    /**
     * Test webhook with missing required fields
     */
    public function test_webhook_rejects_invalid_payload(): void
    {
        config(['services.oto.webhook.strict_verification' => false]);

        $payload = [
            // Missing tracking_number and status
            'invalid_field' => 'value',
        ];

        $response = $this->postJson('/api/webhooks/oto/shipment', $payload);

        $response->assertStatus(400);
        $response->assertJson(['success' => false]);
    }

    /**
     * Test webhook with non-existent order
     */
    public function test_webhook_handles_non_existent_order(): void
    {
        config(['services.oto.webhook.strict_verification' => false]);

        $payload = [
            'tracking_number' => 'NON_EXISTENT',
            'status' => 'delivered',
        ];

        $response = $this->postJson('/api/webhooks/oto/shipment', $payload);

        $response->assertStatus(404);
        $response->assertJson(['success' => false]);
    }

    /**
     * Test webhook is idempotent
     */
    public function test_webhook_is_idempotent(): void
    {
        config(['services.oto.webhook.strict_verification' => false]);

        $payload = [
            'tracking_number' => $this->order->tracking_number,
            'status' => 'out_for_delivery',
            'updated_at' => now()->toDateTimeString(),
        ];

        // Send webhook first time
        $response1 = $this->postJson('/api/webhooks/oto/shipment', $payload);
        $response1->assertStatus(200);

        $this->order->refresh();
        $firstUpdatedAt = $this->order->shipping_status_updated_at;

        // Send same webhook again
        sleep(1); // Ensure timestamp would change if not idempotent
        $response2 = $this->postJson('/api/webhooks/oto/shipment', $payload);
        $response2->assertStatus(200);

        $this->order->refresh();
        // Timestamp should not have changed (idempotent)
        $this->assertEquals($firstUpdatedAt, $this->order->shipping_status_updated_at);
    }

    /**
     * Test webhook can find order by reference
     */
    public function test_webhook_finds_order_by_reference(): void
    {
        config(['services.oto.webhook.strict_verification' => false]);

        $payload = [
            'reference' => $this->order->shipping_reference,
            'status' => 'delivered',
            'updated_at' => now()->toDateTimeString(),
        ];

        $response = $this->postJson('/api/webhooks/oto/shipment', $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->order->refresh();
        $this->assertEquals('delivered', $this->order->tracking_status);
    }

    /**
     * Test webhook updates status correctly for different OTO statuses
     */
    public function test_webhook_updates_status_correctly(): void
    {
        config(['services.oto.webhook.strict_verification' => false]);

        // Test shipped -> out_for_delivery
        $this->postJson('/api/webhooks/oto/shipment', [
            'tracking_number' => $this->order->tracking_number,
            'status' => 'out_for_delivery',
            'updated_at' => now()->toDateTimeString(),
        ]);

        $this->order->refresh();
        $this->assertEquals(Order::STATUS_IN_PROGRESS, $this->order->status);

        // Test out_for_delivery -> delivered
        $this->postJson('/api/webhooks/oto/shipment', [
            'tracking_number' => $this->order->tracking_number,
            'status' => 'delivered',
            'updated_at' => now()->addMinute()->toDateTimeString(),
        ]);

        $this->order->refresh();
        $this->assertEquals(Order::STATUS_DELIVERED, $this->order->status);
    }

    /**
     * Test webhook ignores outdated status updates
     */
    public function test_webhook_ignores_outdated_updates(): void
    {
        config(['services.oto.webhook.strict_verification' => false]);

        // Update order to delivered
        $this->order->update([
            'tracking_status' => 'delivered',
            'status' => Order::STATUS_DELIVERED,
            'shipping_status_updated_at' => now(),
        ]);

        // Try to send older webhook with shipped status
        $payload = [
            'tracking_number' => $this->order->tracking_number,
            'status' => 'shipped',
            'updated_at' => now()->subHour()->toDateTimeString(),
        ];

        $response = $this->postJson('/api/webhooks/oto/shipment', $payload);
        $response->assertStatus(200);

        // Order status should not have changed
        $this->order->refresh();
        $this->assertEquals('delivered', $this->order->tracking_status);
        $this->assertEquals(Order::STATUS_DELIVERED, $this->order->status);
    }
}
