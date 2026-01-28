# Quick Start Guide - n8n Workflows for CityPhone

## 🚀 5-Minute Setup

### Step 1: Import Workflows (2 minutes)

1. Open your n8n instance
2. Click **"Workflows"** → **"Import from File"**
3. Import these files in order:
   - `01-payment-webhook-handler.json`
   - `02-oto-shipping-webhook-handler.json`
   - `03-order-processing-automation.json`
   - `04-scheduled-tasks-automation.json`
   - `05-abandoned-cart-recovery.json`
   - `06-daily-sales-report.json`

### Step 2: Set Environment Variables (2 minutes)

In n8n: **Settings** → **Environment Variables**

Add these variables:

```bash
CITYPHONE_API_URL=https://yourdomain.com
CITYPHONE_API_TOKEN=your_sanctum_token_here
CITYPHONE_APP_URL=https://yourdomain.com
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
EMAIL_SERVICE_URL=https://your-email-service.com/api
ADMIN_EMAIL=admin@yourdomain.com
```

### Step 3: Generate API Token (1 minute)

Run in Laravel Tinker:

```php
$user = \App\Models\User::where('email', 'n8n@yourdomain.com')->first();
$token = $user->createToken('n8n-automation')->plainTextToken;
echo $token;
```

Copy the token and use as `CITYPHONE_API_TOKEN`

### Step 4: Activate Workflows

1. Open each workflow
2. Toggle **"Active"** switch (top right)
3. Workflows will start running automatically!

---

## 📋 Workflow Summary

| Workflow | Trigger | Frequency | Purpose |
|----------|---------|-----------|---------|
| Payment Webhook | Webhook | On-demand | Process payment notifications |
| OTO Shipping | Webhook | On-demand | Update shipment status |
| Order Processing | Cron | Every 5 min | Auto-confirm orders |
| Scheduled Tasks | Cron | Multiple | Sync shipments, expire payments |
| Abandoned Cart | Cron | Every hour | Recover abandoned carts |
| Daily Report | Cron | Daily 9 AM | Generate sales reports |

---

## 🔗 Webhook URLs

After importing, copy these URLs from n8n:

1. **Payment Webhook**: 
   ```
   https://your-n8n-instance.com/webhook/payment-webhook
   ```
   → Configure in payment gateway dashboards

2. **OTO Shipping Webhook**:
   ```
   https://your-n8n-instance.com/webhook/oto-shipment-webhook
   ```
   → Configure in OTO dashboard

---

## ✅ Verification Checklist

- [ ] All 6 workflows imported
- [ ] Environment variables set
- [ ] API token generated and configured
- [ ] HTTP credentials created in n8n
- [ ] Workflows activated
- [ ] Webhook URLs copied
- [ ] External services configured with webhook URLs

---

## 🧪 Test Your Setup

### Test Payment Webhook:
```bash
curl -X POST https://your-n8n-instance.com/webhook/payment-webhook \
  -H "Content-Type: application/json" \
  -d '{"gateway":"amwal","order_id":123,"status":"paid"}'
```

### Test Order Processing:
1. Create a test order in Laravel
2. Wait 5 minutes
3. Check if order status changed to "processing"

### Check Executions:
- Go to **Executions** in n8n
- View recent workflow runs
- Verify no errors

---

## 📚 Full Documentation

See `README.md` for detailed documentation, customization options, and troubleshooting.

---

## 🆘 Need Help?

1. Check execution logs in n8n
2. Verify API endpoints are accessible
3. Test API token manually
4. Review Laravel application logs

**Common Issues:**
- ❌ Workflow not running → Check if activated
- ❌ API errors → Verify token and URL
- ❌ Webhook not received → Check URL configuration

---

**Ready to automate! 🎉**
