<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Location;
use App\Models\Order;
use App\Models\User;
use App\Services\Shipping\Oto\Exceptions\OtoValidationException;
use App\Services\Shipping\OtoShippingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test OTO shipment creation validation
 */
class OtoShipmentCreationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();

        // Create test city
        $city = City::factory()->create([
            'name' => 'Riyadh',
            'name_ar' => 'الرياض',
            'status' => true,
        ]);

        // Create test location
        $this->location = Location::factory()->create([
            'user_id' => $this->user->id,
            'city_id' => $city->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '+966500000000',
            'street_address' => 'Test Street',
        ]);
    }

    /**
     * Test order must be in processing status
     */
    public function test_order_must_be_in_processing_status(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'location_id' => $this->location->id,
            'status' => Order::STATUS_PENDING,
            'delivery_method' => Order::DELIVERY_HOME,
        ]);

        $service = $this->app->make(OtoShippingService::class);

        $this->expectException(OtoValidationException::class);
        $this->expectExceptionMessage('must be in \'processing\' status');

        $service->createShipment($order);
    }

    /**
     * Test order must be for home delivery
     */
    public function test_order_must_be_for_home_delivery(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'location_id' => $this->location->id,
            'status' => Order::STATUS_PROCESSING,
            'delivery_method' => Order::DELIVERY_STORE_PICKUP,
        ]);

        $service = $this->app->make(OtoShippingService::class);

        $this->expectException(OtoValidationException::class);
        $this->expectExceptionMessage('not set for home delivery');

        $service->createShipment($order);
    }

    /**
     * Test order cannot be shipped twice
     */
    public function test_order_cannot_be_shipped_twice(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'location_id' => $this->location->id,
            'status' => Order::STATUS_PROCESSING,
            'delivery_method' => Order::DELIVERY_HOME,
            'tracking_number' => 'TRK123456',
        ]);

        $service = $this->app->make(OtoShippingService::class);

        $this->expectException(OtoValidationException::class);
        $this->expectExceptionMessage('already has an active shipment');

        $service->createShipment($order);
    }

    /**
     * Test order must have valid location
     */
    public function test_order_must_have_valid_location(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'location_id' => null,
            'status' => Order::STATUS_PROCESSING,
            'delivery_method' => Order::DELIVERY_HOME,
        ]);

        $service = $this->app->make(OtoShippingService::class);

        $this->expectException(OtoValidationException::class);
        $this->expectExceptionMessage('Location not set');

        $service->createShipment($order);
    }

    /**
     * Test location must have recipient name
     */
    public function test_location_must_have_recipient_name(): void
    {
        $location = Location::factory()->create([
            'user_id' => $this->user->id,
            'city_id' => $this->location->city_id,
            'first_name' => null,
            'last_name' => null,
            'phone' => '+966500000000',
            'street_address' => 'Test Street',
        ]);

        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'location_id' => $location->id,
            'status' => Order::STATUS_PROCESSING,
            'delivery_method' => Order::DELIVERY_HOME,
        ]);

        $service = $this->app->make(OtoShippingService::class);

        $this->expectException(OtoValidationException::class);
        $this->expectExceptionMessage('Missing recipient name');

        $service->createShipment($order);
    }

    /**
     * Test location must have phone
     */
    public function test_location_must_have_phone(): void
    {
        $location = Location::factory()->create([
            'user_id' => $this->user->id,
            'city_id' => $this->location->city_id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => null,
            'street_address' => 'Test Street',
        ]);

        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'location_id' => $location->id,
            'status' => Order::STATUS_PROCESSING,
            'delivery_method' => Order::DELIVERY_HOME,
        ]);

        $service = $this->app->make(OtoShippingService::class);

        $this->expectException(OtoValidationException::class);
        $this->expectExceptionMessage('Missing recipient phone');

        $service->createShipment($order);
    }

    /**
     * Test location must have address
     */
    public function test_location_must_have_address(): void
    {
        $location = Location::factory()->create([
            'user_id' => $this->user->id,
            'city_id' => $this->location->city_id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '+966500000000',
            'street_address' => null,
        ]);

        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'location_id' => $location->id,
            'status' => Order::STATUS_PROCESSING,
            'delivery_method' => Order::DELIVERY_HOME,
        ]);

        $service = $this->app->make(OtoShippingService::class);

        $this->expectException(OtoValidationException::class);
        $this->expectExceptionMessage('Missing street address');

        $service->createShipment($order);
    }

    /**
     * Test order eligibility helper methods
     */
    public function test_order_eligibility_helper_methods(): void
    {
        // Eligible order
        $eligibleOrder = Order::factory()->create([
            'user_id' => $this->user->id,
            'location_id' => $this->location->id,
            'status' => Order::STATUS_PROCESSING,
            'delivery_method' => Order::DELIVERY_HOME,
        ]);

        $this->assertTrue($eligibleOrder->isEligibleForShipment());
        $this->assertFalse($eligibleOrder->hasActiveShipment());

        // Already shipped order
        $shippedOrder = Order::factory()->create([
            'user_id' => $this->user->id,
            'location_id' => $this->location->id,
            'status' => Order::STATUS_SHIPPED,
            'delivery_method' => Order::DELIVERY_HOME,
            'tracking_number' => 'TRK123456',
        ]);

        $this->assertFalse($shippedOrder->isEligibleForShipment());
        $this->assertTrue($shippedOrder->hasActiveShipment());
        $this->assertTrue($shippedOrder->isBeingShipped());
    }
}


