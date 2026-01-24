# Amwal Webhook Testing Guide

## Overview

Amwal sends webhooks to notify your application about payment status changes. This guide explains how to test webhooks both locally and in production.

## Webhook URL

Your webhook URL is:
```
https://your-domain.com/api/webhooks/payment/amwal
```

For local testing with ngrok:
```
https://your-ngrok-url.ngrok-free.app/api/webhooks/payment/amwal
```

## Testing Methods

### Method 1: Using the Test Script

Run the provided test script to simulate a webhook:

```bash
php test_amwal_webhook.php <order_id>
```

Example:
```bash
php test_amwal_webhook.php 62
```

This will:
- Find the order and transaction
- Simulate an Amwal webhook payload
- Process the webhook
- Update the order status
- Show the results

### Method 2: Using cURL

You can simulate a webhook using cURL:

```bash
curl -X POST http://localhost:8000/api/webhooks/payment/amwal \
  -H "Content-Type: application/json" \
  -H "X-Amwal-Signature: test-signature" \
  -d '{
    "payment_link_id": "0bb4543b-69f1-41f3-be1d-a23bf25dd153",
    "status": "Paid",
    "metadata": {
      "order_id": 62,
      "order_number": "ORD-WCO2MJCB-20260124",
      "user_id": 28
    },
    "amount": "89.00",
    "currency": "SAR"
  }'
```

### Method 3: Using Postman or Similar Tools

1. Create a new POST request
2. URL: `http://localhost:8000/api/webhooks/payment/amwal`
3. Headers:
   - `Content-Type: application/json`
   - `X-Amwal-Signature: test-signature` (optional)
4. Body (JSON):
```json
{
  "payment_link_id": "0bb4543b-69f1-41f3-be1d-a23bf25dd153",
  "status": "Paid",
  "metadata": {
    "order_id": 62,
    "order_number": "ORD-WCO2MJCB-20260124",
    "user_id": 28
  },
  "amount": "89.00",
  "currency": "SAR"
}
```

### Method 4: Using ngrok Web Interface

1. Start ngrok: `ngrok http 8000`
2. Copy the HTTPS URL (e.g., `https://abc123.ngrok-free.app`)
3. Update your `.env`:
   ```
   NGROK_URL=https://abc123.ngrok-free.app
   ```
4. Configure the webhook URL in Amwal dashboard:
   ```
   https://abc123.ngrok-free.app/api/webhooks/payment/amwal
   ```
5. Complete a test payment on Amwal
6. Check logs: `storage/logs/laravel.log`

## Webhook Payload Structure

Amwal may send webhooks in different formats. The handler supports:

### Format 1: Simple Format
```json
{
  "payment_link_id": "0bb4543b-69f1-41f3-be1d-a23bf25dd153",
  "status": "Paid",
  "metadata": {
    "order_id": 62,
    "order_number": "ORD-WCO2MJCB-20260124"
  }
}
```

### Format 2: Nested Format
```json
{
  "payment_link": {
    "id": "0bb4543b-69f1-41f3-be1d-a23bf25dd153",
    "status": "Paid",
    "metadata": {
      "order_id": 62,
      "order_number": "ORD-WCO2MJCB-20260124"
    }
  },
  "transactions": [{
    "status": "success"
  }]
}
```

## Status Mapping

Amwal statuses are mapped as follows:

| Amwal Status | Mapped Status | Description |
|------------|---------------|-------------|
| `Paid` | `success` | Payment completed |
| `success` | `success` | Payment successful |
| `pending` | `pending` | Payment pending |
| `failed` | `failed` | Payment failed |
| `cancelled` | `cancelled` | Payment cancelled |
| `refunded` | `refunded` | Payment refunded |

## Verifying Webhook Processing

After a webhook is processed, check:

1. **Logs**: `storage/logs/laravel.log`
   - Look for: `"Amwal webhook received"`
   - Look for: `"Webhook processed successfully"`
   - Look for: `"Order updated successfully"`

2. **Database**:
   ```php
   $order = Order::find(62);
   echo "Payment Status: " . $order->payment_status . "\n";
   
   $transaction = $order->getLatestPaymentTransaction();
   echo "Transaction Status: " . $transaction->status . "\n";
   ```

3. **Test Script**:
   ```bash
   php test_amwal_callback.php 62
   ```

## Troubleshooting

### Webhook Not Received

1. **Check ngrok is running**: `ngrok http 8000`
2. **Verify webhook URL in Amwal dashboard**: Must be HTTPS
3. **Check firewall**: Ensure port 8000 is accessible
4. **Check logs**: Look for webhook attempts in `laravel.log`

### Webhook Received But Order Not Updated

1. **Check webhook payload**: Look for `"Amwal webhook received"` in logs
2. **Verify order_id in metadata**: Must match an existing order
3. **Check transaction exists**: Order must have a payment transaction
4. **Verify status mapping**: Check if Amwal status is correctly mapped

### Signature Validation Failing

If you've configured `AMWAL_WEBHOOK_SECRET`:
1. Check the signature header name (default: `X-Amwal-Signature`)
2. Verify the signature algorithm matches Amwal's implementation
3. Temporarily disable validation for testing (remove `AMWAL_WEBHOOK_SECRET`)

## Production Setup

For production:

1. **Set webhook URL in Amwal dashboard**:
   ```
   https://your-domain.com/api/webhooks/payment/amwal
   ```

2. **Configure webhook secret** (recommended):
   ```env
   AMWAL_WEBHOOK_SECRET=your-secret-key
   ```

3. **Enable webhook signature validation**:
   - Update `validateWebhookSignature()` in `AmwalGateway.php`
   - Implement Amwal's signature validation algorithm

4. **Monitor webhooks**:
   - Set up logging/alerting for webhook failures
   - Monitor order payment status updates
   - Track webhook processing times

## Testing Checklist

- [ ] Test script runs successfully
- [ ] cURL webhook test works
- [ ] Order status updates to "paid" after webhook
- [ ] Transaction status updates to "success"
- [ ] Logs show webhook processing
- [ ] Webhook URL is accessible (HTTPS)
- [ ] Webhook configured in Amwal dashboard
- [ ] Real payment triggers webhook
- [ ] Failed payments handled correctly

## Support

If you encounter issues:
1. Check `storage/logs/laravel.log` for detailed error messages
2. Run test scripts to verify functionality
3. Verify Amwal API credentials are correct
4. Ensure webhook URL is accessible and uses HTTPS
