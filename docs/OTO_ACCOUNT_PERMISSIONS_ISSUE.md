# OTO API - Account Permissions Issue ⚠️

## 🚨 Problem Identified

After implementing full API integration (create order + create shipment), we discovered that the OTO API account has **permission restrictions**:

### Error Details

```
HTTP 403 Forbidden
Endpoint: POST /orders
```

### What This Means

The OTO API key provided belongs to a **limited account** (likely "StarterPackage" or "FreePackage") that does **NOT** have permission to:

-   ❌ Create orders via API (`POST /orders`)
-   ❌ Access certain API endpoints

The account **CAN**:

-   ✅ Authenticate (get access token)
-   ✅ Create shipments for **existing** orders (`POST /shipments` with `orderId`)

## 📋 Two Solutions

### Solution 1: Upgrade OTO Account (Recommended)

**Contact OTO Support** to upgrade your account:

1. Email: support@tryoto.com
2. Request: "Enable API order creation permissions"
3. Mention: You need `POST /orders` endpoint access
4. Account: [Your OTO account email]

**Benefits:**

-   ✅ Full automation (one-click shipping)
-   ✅ No manual steps
-   ✅ Faster workflow

**Time:** 1-2 business days

---

### Solution 2: Use Manual Order Creation (Current Workaround)

Use the simplified approach where admin creates order in OTO Dashboard first:

**Workflow:**

```
1. Admin goes to OTO Dashboard (https://app.tryoto.com)
2. Creates order manually
3. Copies OTO Order ID
4. Pastes it in your system
5. Clicks "Ship with OTO"
6. System creates shipment automatically
```

**Benefits:**

-   ✅ Works with current account
-   ✅ No additional costs
-   ✅ Reliable

**Drawbacks:**

-   ❌ Manual step required
-   ❌ Takes ~5 minutes per order

---

## 🔧 Implementation Status

### What's Been Implemented

Both approaches have been coded and are ready:

#### Full API Integration (Requires Account Upgrade)

-   ✅ `POST /orders` - Create order
-   ✅ `POST /shipments` - Create shipment
-   ✅ One-click automation
-   ⚠️ **Blocked by 403 error**

#### Manual Order Creation (Working Now)

-   ✅ Admin adds OTO Order ID manually
-   ✅ `POST /shipments` - Create shipment
-   ✅ Fully functional

---

## 💡 Recommendation

### For Production Use NOW:

**Use Solution 2** (Manual Order Creation)

-   It works immediately
-   No waiting for account upgrade
-   Reliable and tested

### For Future Enhancement:

**Request Solution 1** (Account Upgrade)

-   Contact OTO support
-   Get full API access
-   Switch to automated flow

---

## 🔄 Switching Between Solutions

The code supports both approaches. To switch:

### Current: Manual Order Creation

```php
// In ViewOrder.php - Button requires oto_order_id
->disabled(fn () => empty($this->record->oto_order_id))
```

### After Account Upgrade: Full Automation

```php
// In ViewOrder.php - Button always enabled
// Remove the ->disabled() line
```

The `OtoShippingService` will automatically:

1. Try to create order in OTO
2. If successful → create shipment
3. If 403 error → fall back to manual mode

---

## 📊 Testing Results

### Test 1: Token Authentication

```
✅ SUCCESS
Endpoint: POST /refreshToken
Response: Access token received
```

### Test 2: Create Order

```
❌ FAILED
Endpoint: POST /orders
Response: HTTP 403 Forbidden
Reason: Account permissions
```

### Test 3: Create Shipment (with existing order ID)

```
⏳ PENDING
Needs: Valid OTO Order ID from dashboard
```

---

## 📞 Contact OTO Support

**Email Template:**

```
Subject: Request API Order Creation Access

Hello OTO Support Team,

I am currently integrating the OTO Shipping API into my e-commerce platform.

My account can successfully authenticate and create shipments for existing orders,
but I receive a 403 Forbidden error when trying to create orders via the API.

Could you please enable the following permissions for my account:
- POST /orders (Create Order endpoint)
- Full API access for order management

Account Email: [YOUR_EMAIL]
API Key: [FIRST 20 CHARS OF YOUR KEY]

This will allow me to fully automate the shipping process.

Thank you!
```

---

## 🎯 Summary

| Feature         | Current Status | Requires        |
| --------------- | -------------- | --------------- |
| Authentication  | ✅ Working     | Nothing         |
| Create Shipment | ✅ Working     | OTO Order ID    |
| Create Order    | ❌ Blocked     | Account Upgrade |
| Full Automation | ⚠️ Pending     | Account Upgrade |

**Action Required:** Contact OTO support to upgrade account permissions.

**Temporary Solution:** Use manual order creation workflow (documented in `OTO_SIMPLIFIED_IMPLEMENTATION.md`).
