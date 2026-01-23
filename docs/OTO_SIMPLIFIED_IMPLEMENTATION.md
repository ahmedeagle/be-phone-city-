# OTO Shipping Integration - Simplified Implementation

## 📋 Overview

This document describes the simplified OTO shipping integration that has been implemented based on the correct understanding of the OTO API capabilities.

## 🎯 Key Understanding

**The OTO API does NOT have a `/createOrder` endpoint for direct order creation.**

Orders must already exist in OTO before you can create a shipment. There are three ways to get orders into OTO:
1. **Marketplace Integrations** (WooCommerce, Shopify, Salla, Zid)
2. **Manual Entry** through OTO Dashboard
3. **Webhook Integration** (requires OTO support setup)

## ✅ Implemented Solution

We've implemented **Approach 1: Simple Shipment Only** from the requirements document.

### How It Works

1. Admin creates order in OTO Dashboard manually
2. Admin copies the OTO Order ID
3. Admin pastes it into the order in our system
4. Admin clicks "Ship with OTO" button
5. System creates shipment using the OTO Order ID
6. System receives tracking number and updates order

## 📝 Changes Made

### 1. Database Changes

**New Migration:** `2026_01_09_163337_add_oto_order_id_to_orders_table.php`

```php
Schema::table('orders', function (Blueprint $table) {
    $table->string('oto_order_id')->nullable()->after('order_number');
    $table->index('oto_order_id');
});
```

The `oto_order_id` field stores the OTO Order ID that links our order to the OTO system.

### 2. Service Layer Simplification

**File:** `app/Services/Shipping/OtoShippingService.php`

**Key Changes:**
- Simplified `createShipment()` method to only use `oto_order_id`
- Removed complex payload building (no longer needed)
- Removed location validation (OTO already has this data)
- Removed helper methods: `buildShipmentPayload()`, `buildShipmentDescription()`, `calculateTotalWeight()`, `getCodAmount()`

**New Payload:**
```php
$payload = [
    'orderId' => $order->oto_order_id,
    'deliveryOptionId' => $deliveryOptionId, // optional
];
```

### 3. Validation Updates

**File:** `app/Services/Shipping/Oto/Exceptions/OtoValidationException.php`

**New Method:**
```php
public static function missingOtoOrderId(Order $order): self
{
    return new self(
        "Order #{$order->order_number} does not have OTO Order ID. " .
        "Please create the order in OTO Dashboard first and add the Order ID."
    );
}
```

This provides clear error messages when trying to ship without an OTO Order ID.

### 4. Filament Form Updates

**File:** `app/Filament/Admin/Resources/Orders/Schemas/OrderForm.php`

**Added Section:**
- New collapsible section: "معلومات الشحن مع OTO" (OTO Shipping Information)
- Help text with step-by-step instructions in Arabic
- Link to OTO Dashboard
- Text input field for `oto_order_id`

### 5. ViewOrder Action Updates

**File:** `app/Filament/Admin/Resources/Orders/Pages/ViewOrder.php`

**Changes:**
- Updated modal description to show different messages based on whether `oto_order_id` exists
- Added `->disabled()` when `oto_order_id` is empty
- Removed all debug logging statements
- Cleaner error handling

## 🔄 User Workflow

```
1. Customer places order
   ↓
2. Order created in your system (status: pending/processing)
   ↓
3. Admin goes to OTO Dashboard (https://app.tryoto.com)
   - Clicks "Add Order"
   - Fills in customer details, items, address
   - Saves order
   - Copies OTO Order ID (e.g., "123456")
   ↓
4. Admin returns to your admin panel
   - Opens the order
   - Clicks "Edit" or expands "OTO Shipping Information"
   - Pastes OTO Order ID
   - Saves
   ↓
5. Admin clicks "Ship with OTO"
   - System validates order
   - Creates shipment via OTO API
   - Receives tracking number
   - Updates order status to "shipped"
   ↓
6. Customer receives tracking information
```

## 🎨 UI Features

### Order Form
- Collapsible section for OTO shipping
- Clear instructions in Arabic
- Direct link to OTO Dashboard
- Helper text explaining the process

### View Order Page
- "Ship with OTO" button
- Disabled when no OTO Order ID
- Dynamic modal description
- Clear error messages
- Success notification with tracking link

## 🔧 Authentication (Unchanged)

The authentication mechanism is **working correctly** and was **NOT changed**:

```php
// Refresh token exchange
POST /refreshToken
{
    "refresh_token": "YOUR_OTO_API_KEY"
}

// Returns access token
{
    "access_token": "jwt_token_here",
    "expires_in": 3600
}

// Use in requests
Authorization: Bearer {access_token}
```

## 📊 Database State Example

```sql
-- Before adding OTO Order ID
id | order_number | oto_order_id | tracking_number | status
1  | ORD-001      | NULL         | NULL            | processing

-- After admin adds OTO Order ID
id | order_number | oto_order_id | tracking_number | status
1  | ORD-001      | 123456       | NULL            | processing

-- After creating shipment
id | order_number | oto_order_id | tracking_number | status
1  | ORD-001      | 123456       | AWB789012345    | shipped
```

## ✅ Benefits of This Approach

1. **Simple & Reliable** - Uses OTO's existing order system
2. **No Complex Logic** - No need to map all order data to OTO format
3. **Clear Workflow** - Admin knows exactly what to do
4. **Better Error Handling** - Clear messages guide the user
5. **Production Ready** - Works with current OTO API capabilities

## ⚠️ Limitations

1. **Manual Step Required** - Admin must create order in OTO Dashboard
2. **Two Systems** - Orders exist in both systems
3. **Extra Work** - Admin enters order twice (initially)

## 🚀 Future Enhancement

For automatic order creation, you can implement webhook integration:
1. Register webhook endpoint in OTO Dashboard
2. Push order details when order is created
3. OTO creates order and returns Order ID
4. Save Order ID automatically
5. Create shipment programmatically

**This requires coordination with OTO support for webhook configuration.**

## 🧪 Testing

### Test with Real Order

```bash
php artisan tinker
```

```php
// Get an order
$order = Order::first();

// Add OTO Order ID (use a real one from OTO Dashboard)
$order->update(['oto_order_id' => '123456']);

// Test shipment creation
$service = app(OtoShippingService::class);
$shipment = $service->createShipment($order);

// Check result
echo $shipment->trackingNumber;
```

### Test Validation

```php
// Should throw OtoValidationException
$orderWithoutOtoId = Order::whereNull('oto_order_id')->first();
$service->createShipment($orderWithoutOtoId);
// Error: "Order #ORD-XXX does not have OTO Order ID..."
```

## 📞 OTO Dashboard Access

**URL:** https://app.tryoto.com

**Steps to Create Order:**
1. Login to OTO Dashboard
2. Navigate to Orders → Add Order
3. Fill in:
   - Customer information
   - Delivery address
   - Order items
   - Payment method (COD/Prepaid)
4. Save order
5. Copy the Order ID from the order details

## 🔐 Environment Variables

No changes needed to existing configuration:

```env
OTO_API_KEY=your_refresh_token_from_dashboard
OTO_ENVIRONMENT=production
OTO_BASE_URL=https://api.tryoto.com/rest/v2
OTO_TIMEOUT=30
```

## 📚 Related Files

- `database/migrations/2026_01_09_163337_add_oto_order_id_to_orders_table.php`
- `app/Services/Shipping/OtoShippingService.php`
- `app/Services/Shipping/Oto/Exceptions/OtoValidationException.php`
- `app/Filament/Admin/Resources/Orders/Schemas/OrderForm.php`
- `app/Filament/Admin/Resources/Orders/Pages/ViewOrder.php`

## ✨ Summary

This implementation provides a **practical, working solution** for OTO shipping integration that:
- ✅ Works with the current OTO API
- ✅ Requires minimal code changes
- ✅ Provides clear user guidance
- ✅ Has proper error handling
- ✅ Is production-ready

The authentication and API communication are **working correctly** and should not be changed.

