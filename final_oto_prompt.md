# FINAL: OTO Shipping Integration - Complete Implementation Guide

## 🎯 Critical Understanding After Research

After extensive documentation review, here's the CORRECT understanding:

### OTO API Does NOT Have `/createOrder` Endpoint!

**How Orders Get Into OTO:**
1. **Marketplace Integrations** - WooCommerce, Shopify, Salla, Zid (automatic)
2. **Manual Entry** - Through OTO Dashboard
3. **Webhook Integration** - OTO receives orders via webhook from your system

**For Direct API Integration (like ours), there are TWO approaches:**

---

## 📋 Approach 1: Simple Shipment Only (RECOMMENDED FOR NOW)

This approach assumes orders already exist in OTO (manually added or via integration).

### What This Does:
- Admin manually adds order to OTO Dashboard first
- Gets OTO Order ID
- Stores it in database
- Then creates shipment via API

### Implementation:

#### 1. Database Migration

```bash
php artisan make:migration add_oto_order_id_to_orders_table
```

```php
Schema::table('orders', function (Blueprint $table) {
    $table->string('oto_order_id')->nullable()->after('order_number');
    $table->index('oto_order_id');
});
```

#### 2. Keep Existing OtoHttpClient (Authentication is CORRECT!)

**DO NOT CHANGE** the refresh token authentication - it's working correctly:

```php
// This is CORRECT - keep it!
protected function refreshAccessToken(string $cacheKey): string
{
    $response = Http::timeout($this->timeout)
        ->post($this->baseUrl . '/refreshToken', [
            'refresh_token' => $this->apiKey,
        ]);
    
    $data = $response->json();
    $token = $data['access_token'];
    
    Cache::put($cacheKey, $token, $data['expires_in'] - 300);
    
    return $token;
}

protected function client(): PendingRequest
{
    $token = $this->getAccessToken();
    
    return Http::timeout($this->timeout)
        ->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Content-Type' => 'application/json',
        ])
        ->baseUrl($this->baseUrl);
}
```

#### 3. Simplify OtoShippingService

```php
public function createShipment(Order $order, ?int $deliveryOptionId = null): OtoShipmentDto
{
    // Validate order
    $this->validateOrderForShipment($order);
    
    // Check if order has OTO ID
    if (empty($order->oto_order_id)) {
        throw OtoValidationException::missingOtoOrderId($order);
    }
    
    // Create shipment using OTO order ID
    $payload = [
        'orderId' => $order->oto_order_id,
    ];
    
    if ($deliveryOptionId) {
        $payload['deliveryOptionId'] = $deliveryOptionId;
    }
    
    Log::info('Creating OTO shipment', [
        'order_id' => $order->id,
        'oto_order_id' => $order->oto_order_id,
    ]);
    
    $response = $this->client->createShipment($payload);
    $shipmentDto = OtoShipmentDto::fromApiResponse($response);
    
    $this->updateOrderWithShipment($order, $shipmentDto);
    
    return $shipmentDto;
}
```

#### 4. Add Field to Filament Form

In `OrderResource.php` form:

```php
Forms\Components\TextInput::make('oto_order_id')
    ->label('OTO Order ID')
    ->helperText('Get this from OTO Dashboard after creating the order manually')
    ->placeholder('e.g., 123456')
    ->visible(fn ($livewire) => $livewire instanceof EditRecord),
```

#### 5. Update ViewOrder Action

```php
Action::make('ship_with_oto')
    ->label('Ship with OTO')
    ->icon('heroicon-o-truck')
    ->color('success')
    ->requiresConfirmation()
    ->modalDescription(function ($record) {
        if (empty($record->oto_order_id)) {
            return 'Please add OTO Order ID first. Go to OTO Dashboard → Add Order → Copy Order ID → Edit this order and paste it.';
        }
        return 'Create shipment for this order in OTO?';
    })
    ->action(function () {
        try {
            $service = app(OtoShippingService::class);
            $shipment = $service->createShipment($this->record);
            
            Notification::make()
                ->title('Shipment Created!')
                ->body("Tracking: {$shipment->trackingNumber}")
                ->success()
                ->send();
        } catch (OtoValidationException $e) {
            Notification::make()
                ->title('Validation Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    })
    ->visible(fn ($record) => 
        $record->status === Order::STATUS_PROCESSING
        && $record->delivery_method === Order::DELIVERY_HOME
    )
    ->disabled(fn ($record) => empty($record->oto_order_id)),
```

#### 6. Add Exception

```php
// OtoValidationException.php
public static function missingOtoOrderId(Order $order): self
{
    return new self(
        "Order #{$order->order_number} does not have OTO Order ID. " .
        "Please create the order in OTO Dashboard first and add the Order ID."
    );
}
```

---

## 📋 Approach 2: Full Integration (FUTURE ENHANCEMENT)

For automatic order creation, you'll need to implement webhook integration where OTO receives orders from your system.

### Steps:
1. Register webhook endpoint in OTO Dashboard
2. Push order details when order is created
3. OTO creates order and returns Order ID
4. Save Order ID
5. Create shipment

**This requires more complex setup and OTO support for webhook configuration.**

---

## ✅ Implementation Checklist (Approach 1)

### Changes Needed:

- [ ] Run migration to add `oto_order_id` column
- [ ] **KEEP** existing OtoHttpClient authentication (it's correct!)
- [ ] Simplify `createShipment()` to only check for `oto_order_id`
- [ ] Add `oto_order_id` field to Filament form
- [ ] Update ViewOrder action with proper messaging
- [ ] Add validation exception for missing OTO ID
- [ ] Test workflow

### DO NOT Change:
- ❌ Authentication mechanism (refresh token → access token)
- ❌ OtoHttpClient structure
- ❌ Token caching logic
- ❌ Bearer token in Authorization header

---

## 🔄 User Workflow

```
1. Customer places order → Order created in your system

2. Admin receives order notification

3. Admin goes to OTO Dashboard:
   - Clicks "Add Order"
   - Fills in customer details, items, address
   - Saves → Gets OTO Order ID (e.g., 123456)

4. Admin returns to your admin panel:
   - Opens the order
   - Clicks "Edit"
   - Pastes OTO Order ID in the field
   - Saves

5. Admin clicks "Ship with OTO":
   - System uses OTO Order ID
   - Creates shipment via API
   - Gets tracking number
   - Updates order status

6. Done! Customer can track shipment
```

---

## 🎨 Filament UI Improvements

### Add Info Message

```php
Forms\Components\Placeholder::make('oto_integration_help')
    ->label('OTO Integration')
    ->content(new HtmlString('
        <div class="text-sm text-gray-600">
            <p class="font-semibold mb-2">How to ship with OTO:</p>
            <ol class="list-decimal list-inside space-y-1">
                <li>Create order in <a href="https://app.tryoto.com" target="_blank" class="text-primary-600 hover:underline">OTO Dashboard</a></li>
                <li>Copy the OTO Order ID</li>
                <li>Paste it in the field below</li>
                <li>Save and click "Ship with OTO"</li>
            </ol>
        </div>
    '))
    ->visible(fn ($livewire) => $livewire instanceof EditRecord),
```

---

## 📊 Database State

```sql
-- Before shipping
orders table:
id  | order_number | oto_order_id | tracking_number | status
1   | ORD-001      | NULL         | NULL            | processing

-- After admin adds OTO ID
id  | order_number | oto_order_id | tracking_number | status
1   | ORD-001      | 123456       | NULL            | processing

-- After shipment created
id  | order_number | oto_order_id | tracking_number | status
1   | ORD-001      | 123456       | AWB789012       | shipped
```

---

## 🧪 Testing

```php
php artisan tinker

// Test case: Order with OTO ID
>>> $order = Order::find(1);
>>> $order->update(['oto_order_id' => '123456']); // Use real OTO order ID
>>> $service = app(OtoShippingService::class);
>>> $shipment = $service->createShipment($order);
>>> $shipment->trackingNumber;

// Test case: Order without OTO ID (should throw exception)
>>> $order2 = Order::find(2);
>>> $service->createShipment($order2); // Should throw OtoValidationException
```

---

## 🎯 Why This Approach?

**Pros:**
✅ Works immediately with current OTO API
✅ No complex order creation logic
✅ Clear admin workflow
✅ Reliable - uses OTO's existing order system
✅ No changes to authentication (which is working)

**Cons:**
❌ Manual step required (adding OTO Order ID)
❌ Two systems to manage

**Future Enhancement:**
- Implement webhook integration for automatic order sync
- This requires OTO support and webhook configuration

---

## 📝 Configuration

**.env:**
```bash
# OTO Configuration
OTO_API_KEY=your_refresh_token_from_dashboard
OTO_ENVIRONMENT=production
OTO_BASE_URL=https://api.tryoto.com/rest/v2
OTO_TIMEOUT=30
```

**config/services.php:** (Keep existing configuration)

---

## 🚨 Common Issues & Solutions

### Issue: "Order not found in OTO"
**Solution:** Verify the OTO Order ID is correct. Check OTO Dashboard.

### Issue: "Authentication failed"
**Solution:** Verify refresh token in .env. Get new one from OTO Dashboard → Sales Channel → OTO API.

### Issue: "Shipment already exists"
**Solution:** Order already has a shipment. Use cancel shipment first if needed.

---

## 📞 Getting OTO Order ID

1. Login to OTO Dashboard: https://app.tryoto.com
2. Go to "Orders" → "Add Order"
3. Fill in all required fields:
   - Customer info
   - Delivery address
   - Order items
   - Payment method
4. Click "Save"
5. Copy the Order ID displayed (e.g., "123456")
6. Return to your admin panel and paste it

---

## 🎉 Summary

**Key Points:**
- OTO API does NOT have `/createOrder` endpoint for direct use
- Orders must be in OTO system first (manual or marketplace integration)
- Our solution: Admin adds Order ID manually before shipping
- Authentication code is CORRECT - don't change it!
- Simple, reliable, works with current API

**Implementation Time:** ~2 hours
**Complexity:** Low
**Reliability:** High

This is the **practical, working solution** based on actual OTO API capabilities.