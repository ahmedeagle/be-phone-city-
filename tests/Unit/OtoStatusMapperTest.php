<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Services\Shipping\Oto\OtoStatusMapper;
use PHPUnit\Framework\TestCase;

/**
 * Test OTO status mapping logic
 */
class OtoStatusMapperTest extends TestCase
{
    /**
     * Test mapping created/shipped statuses
     */
    public function test_maps_created_statuses_to_shipped(): void
    {
        $statuses = ['created', 'picked_up', 'in_transit', 'shipped', 'at_warehouse'];

        foreach ($statuses as $status) {
            $result = OtoStatusMapper::mapToOrderStatus($status);
            $this->assertEquals(Order::STATUS_SHIPPED, $result, "Failed for status: {$status}");
        }
    }

    /**
     * Test mapping out for delivery statuses
     */
    public function test_maps_out_for_delivery_to_in_progress(): void
    {
        $statuses = ['out_for_delivery', 'on_delivery', 'in_delivery', 'delivering'];

        foreach ($statuses as $status) {
            $result = OtoStatusMapper::mapToOrderStatus($status);
            $this->assertEquals(Order::STATUS_IN_PROGRESS, $result, "Failed for status: {$status}");
        }
    }

    /**
     * Test mapping delivered statuses
     */
    public function test_maps_delivered_statuses_to_delivered(): void
    {
        $statuses = ['delivered', 'completed', 'success'];

        foreach ($statuses as $status) {
            $result = OtoStatusMapper::mapToOrderStatus($status);
            $this->assertEquals(Order::STATUS_DELIVERED, $result, "Failed for status: {$status}");
        }
    }

    /**
     * Test cancelled/failed statuses return null
     */
    public function test_cancelled_statuses_return_null(): void
    {
        $statuses = ['cancelled', 'failed', 'returned', 'return_to_sender'];

        foreach ($statuses as $status) {
            $result = OtoStatusMapper::mapToOrderStatus($status);
            $this->assertNull($result, "Failed for status: {$status}");
        }
    }

    /**
     * Test processing statuses
     */
    public function test_maps_processing_statuses_to_processing(): void
    {
        $statuses = ['pending', 'processing', 'awaiting_pickup'];

        foreach ($statuses as $status) {
            $result = OtoStatusMapper::mapToOrderStatus($status);
            $this->assertEquals(Order::STATUS_PROCESSING, $result, "Failed for status: {$status}");
        }
    }

    /**
     * Test case insensitivity
     */
    public function test_status_mapping_is_case_insensitive(): void
    {
        $this->assertEquals(Order::STATUS_SHIPPED, OtoStatusMapper::mapToOrderStatus('CREATED'));
        $this->assertEquals(Order::STATUS_SHIPPED, OtoStatusMapper::mapToOrderStatus('Created'));
        $this->assertEquals(Order::STATUS_SHIPPED, OtoStatusMapper::mapToOrderStatus('CrEaTeD'));
    }

    /**
     * Test handling spaces and dashes in statuses
     */
    public function test_handles_spaces_and_dashes(): void
    {
        $this->assertEquals(Order::STATUS_IN_PROGRESS, OtoStatusMapper::mapToOrderStatus('out-for-delivery'));
        $this->assertEquals(Order::STATUS_IN_PROGRESS, OtoStatusMapper::mapToOrderStatus('out for delivery'));
        $this->assertEquals(Order::STATUS_IN_PROGRESS, OtoStatusMapper::mapToOrderStatus('out_for_delivery'));
    }

    /**
     * Test unknown status returns null
     */
    public function test_unknown_status_returns_null(): void
    {
        $this->assertNull(OtoStatusMapper::mapToOrderStatus('unknown_status'));
        $this->assertNull(OtoStatusMapper::mapToOrderStatus('invalid'));
    }

    /**
     * Test badge color mapping
     */
    public function test_badge_colors(): void
    {
        $this->assertEquals('success', OtoStatusMapper::getBadgeColor('delivered'));
        $this->assertEquals('warning', OtoStatusMapper::getBadgeColor('out_for_delivery'));
        $this->assertEquals('info', OtoStatusMapper::getBadgeColor('shipped'));
        $this->assertEquals('danger', OtoStatusMapper::getBadgeColor('failed'));
        $this->assertEquals('gray', OtoStatusMapper::getBadgeColor('unknown'));
    }

    /**
     * Test status checks
     */
    public function test_status_checks(): void
    {
        $this->assertTrue(OtoStatusMapper::isInTransit('shipped'));
        $this->assertTrue(OtoStatusMapper::isInTransit('out_for_delivery'));
        $this->assertFalse(OtoStatusMapper::isInTransit('delivered'));

        $this->assertTrue(OtoStatusMapper::isComplete('delivered'));
        $this->assertTrue(OtoStatusMapper::isComplete('completed'));
        $this->assertFalse(OtoStatusMapper::isComplete('shipped'));

        $this->assertTrue(OtoStatusMapper::isFailed('failed'));
        $this->assertTrue(OtoStatusMapper::isFailed('cancelled'));
        $this->assertFalse(OtoStatusMapper::isFailed('delivered'));
    }
}


