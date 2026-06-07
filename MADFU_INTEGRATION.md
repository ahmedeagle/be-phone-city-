# Madfu Payment Integration

**Site:** https://cityphonesa.com  
**Gateway File:** `app/Services/PaymentGateways/MadfuGateway.php`  
**Merchant ID:** MCT76  
**Current Environment:** Staging  

---

## Credentials

| Key | Value |
|-----|-------|
| API URL (Staging) | `https://api.staging.madfu.com.sa` |
| API URL (Production) | `https://api.madfu.com.sa` |
| AppCode | `6zC5N69e5o` |
| APIKey | `tJ4HaseYxO5YVYQ4LSqQkAG9b` |
| Username | `Cashier@cityphone.sa` |
| PlatformTypeId | `5` |
| Branch ID | `1` |

---

## Flow Overview

```
Customer Checkout
      │
      ▼
Step 1: POST /merchants/token/init       → session token
      │
      ▼
Step 2: POST /Merchants/sign-in          → JWT (cached 6 days)
      │
      ▼
Step 3: POST /Merchants/Checkout/CreateOrder  → checkout token
      │
      ▼
Step 4: Redirect customer to checkout-staging.madfu.com.sa/{token}
      │
      ▼
Step 5: Madfu POSTs webhook → https://cityphonesa.com/api/payment/webhook/madfu
      │
      ▼
Step 6: Order status updated in our DB
```

---

## Step 1 — Get Session Token

```
POST https://api.staging.madfu.com.sa/merchants/token/init
```

**Headers:**
```
Authorization:  Basic TUNUNzY6S1ZnZTc4dnplZ0VhOFZYSFBEUUVMNUxNc2pXd0FkbVdyMGU=
APIKey:         tJ4HaseYxO5YVYQ4LSqQkAG9b
AppCode:        6zC5N69e5o
PlatformTypeId: 5
Content-Type:   application/json
```

**Body:**
```json
{
  "uuid": "<random-uuid>",
  "systemInfo": "web"
}
```

**Response:** `{ "token": "<session_token>" }`

---

## Step 2 — Sign In (get JWT)

```
POST https://api.staging.madfu.com.sa/Merchants/sign-in
```

**Headers:** same as Step 1, plus:
```
Token: <session_token from Step 1>
```

**Body:**
```json
{
  "username": "Cashier@cityphone.sa",
  "password": "Welcome@123"
}
```

**Response:** `{ "token": "<jwt>" }`  
JWT is cached for **6 days**. On 401, we force-refresh once and retry.

---

## Step 3 — Create Order

```
POST https://api.staging.madfu.com.sa/Merchants/Checkout/CreateOrder
```

**Headers:** same as Step 1, plus:
```
Token: <JWT from Step 2>
```

**Full Payload:**
```json
{
  "Order": {
    "MerchantReference": "ORDER-123",
    "TotalAmount": 500.00,
    "Amount": 500.00,
    "ActualValue": 500.00,
    "PaidAmount": 0,
    "Vat": 65.22,
    "DeliveryAmount": 0.00,
    "DiscountAmount": 0.00,
    "Branch": 1,
    "OrderDetails": [
      {
        "ProductName": "iPhone 15",
        "Quantity": 1,
        "Price": 500.00
      }
    ]
  },
  "GuestOrderData": {
    "FullName": "Ahmed Ali",
    "Email": "ahmed@example.com",
    "Mobile": "512345678",
    "CustomerMobile": "512345678",
    "City": "Riyadh",
    "Address": "King Fahd Road",
    "Country": "SA"
  },
  "MerchantUrls": {
    "SuccessUrl": "https://cityphonesa.com/api/payment/callback?order=123&status=success",
    "FailUrl":    "https://cityphonesa.com/api/payment/callback?order=123&status=failure",
    "CancelUrl":  "https://cityphonesa.com/api/payment/callback?order=123&status=failure",
    "WebhookUrl": "https://cityphonesa.com/api/payment/webhook/madfu"
  }
}
```

> **Note:** `Mobile` and `CustomerMobile` must be **9 digits** in `5XXXXXXXX` format (no country code).

**Response:** `{ "token": "<checkout_token>", "invoiceCode": "...", "orderId": "..." }`

---

## Step 4 — Customer Redirect

After CreateOrder, redirect customer to:

```
# Staging
https://checkout-staging.madfu.com.sa/{checkout_token}?mobile=512345678

# Production
https://checkout.madfu.com.sa/{checkout_token}?mobile=512345678
```

The `?mobile=` param pre-fills the phone field on the Madfu checkout page.

---

## Step 5 — Webhook (Madfu → Us)

Madfu POSTs to our webhook URL on every status change:

```
POST https://cityphonesa.com/api/payment/webhook/madfu
```

**Signature validation:** `X-Madfu-Signature` header (HMAC-SHA256)

**Status Codes:**

| Code | Meaning |
|------|---------|
| 124 | Payment successful |
| 125 | Payment failed |
| 135 | Order cancelled |
| 136 | Refunded |

> **Known quirk:** Madfu webhook payload uses the field name `invoceCode` (typo — missing `i`). We handle both spellings.

---

## Step 6 — Callback URLs (Customer Redirect Back)

| Event | Our URL |
|-------|---------|
| Success | `https://cityphonesa.com/api/payment/callback?order={id}&status=success` |
| Failure | `https://cityphonesa.com/api/payment/callback?order={id}&status=failure` |

---

## Headers Reference

| Header | Value | Required On |
|--------|-------|-------------|
| `Authorization` | `Basic <base64>` | All calls |
| `APIKey` | `tJ4HaseYxO5YVYQ4LSqQkAG9b` | All calls |
| `AppCode` | `6zC5N69e5o` | All calls |
| `PlatformTypeId` | `5` | All calls |
| `Token` | `<JWT>` (no Bearer prefix) | Step 2+ |
| `Content-Type` | `application/json` | All calls |

---

## Production Credentials (Ready — Pending Go-Live Approval)

| Key | Value |
|-----|-------|
| AppCode | `88G69xHMst` |
| APIKey | `jSzhCnqQIHozASGjHGT91Lezp` |
| Checkout URL | `https://checkout.madfu.com.sa` |
