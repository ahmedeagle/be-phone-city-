# n8n Workflows for CityPhone E-Commerce Platform

This directory contains pre-configured n8n workflows that can be imported to automate various processes in your CityPhone Laravel application.

## 📋 Available Workflows

### 1. Payment Webhook Handler (`01-payment-webhook-handler.json`)
**Purpose**: Automatically process payment webhooks from payment gateways (Amwal, Moyasar, Tabby, Tamara)

**Features**:
- Receives payment webhooks from external gateways
- Validates and forwards to Laravel API
- Sends notifications to admins
- Updates order status automatically

**Trigger**: Webhook (POST request from payment gateway)

---

### 2. OTO Shipping Webhook Handler (`02-oto-shipping-webhook-handler.json`)
**Purpose**: Process shipment status updates from OTO shipping service

**Features**:
- Receives shipment status webhooks from OTO
- Updates order tracking information
- Sends delivery notifications to customers
- Handles delivery confirmations

**Trigger**: Webhook (POST request from OTO)

---

### 3. Order Processing Automation (`03-order-processing-automation.json`)
**Purpose**: Automatically process and confirm orders

**Features**:
- Checks for new paid orders every 5 minutes
- Auto-confirms eligible orders
- Creates shipments for home delivery orders
- Sends confirmation notifications

**Trigger**: Cron (every 5 minutes)

---

### 4. Scheduled Tasks Automation (`04-scheduled-tasks-automation.json`)
**Purpose**: Run scheduled maintenance tasks

**Features**:
- Syncs OTO shipment statuses every 30 minutes
- Marks expired payments every 5 minutes
- Sends reports to admins
- Notifies customers about expired payments

**Trigger**: Cron (multiple schedules)

---

### 5. Abandoned Cart Recovery (`05-abandoned-cart-recovery.json`)
**Purpose**: Recover abandoned shopping carts

**Features**:
- Detects abandoned carts (1 hour, 24 hours, 48 hours)
- Sends reminder emails
- Offers discount codes after 24 hours
- Tracks recovery rates

**Trigger**: Cron (every hour)

---

### 6. Daily Sales Report (`06-daily-sales-report.json`)
**Purpose**: Generate and send daily sales reports

**Features**:
- Calculates daily sales metrics
- Saves data to Google Sheets
- Sends email reports to admins
- Posts summary to Slack

**Trigger**: Cron (daily at 9 AM)

---

## 🚀 Installation & Setup

### Prerequisites

1. **n8n Instance**: You need an n8n instance running (cloud or self-hosted)
   - Cloud: Sign up at [n8n.io](https://n8n.io)
   - Self-hosted: Follow [n8n installation guide](https://docs.n8n.io/hosting/installation/)

2. **API Credentials**: You need API access to your Laravel application
   - API Base URL: `https://yourdomain.com/api`
   - API Token: Generate a Sanctum token for n8n

3. **Environment Variables**: Set up the following in n8n:
   ```
   CITYPHONE_API_URL=https://yourdomain.com
   CITYPHONE_API_TOKEN=your_sanctum_token_here
   CITYPHONE_APP_URL=https://yourdomain.com
   SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
   EMAIL_SERVICE_URL=https://your-email-service.com/api
   ADMIN_EMAIL=admin@yourdomain.com
   GOOGLE_SHEETS_WEBHOOK_URL=https://your-google-sheets-webhook-url
   ```

### Step 1: Import Workflows

1. **Open n8n**: Navigate to your n8n instance
2. **Click "Workflows"** in the sidebar
3. **Click "Import from File"** button
4. **Select a JSON file** from this directory
5. **Click "Import"**

Repeat for each workflow you want to use.

### Step 2: Configure Environment Variables

1. In n8n, go to **Settings** → **Environment Variables**
2. Add all required environment variables (listed above)
3. Save the settings

### Step 3: Configure Credentials

For each workflow that makes API calls, you need to set up HTTP authentication:

1. Go to **Credentials** in n8n sidebar
2. Click **"Add Credential"**
3. Select **"HTTP Header Auth"**
4. Configure:
   - **Name**: `CityPhone API`
   - **Header Name**: `Authorization`
   - **Header Value**: `Bearer {{ $env.CITYPHONE_API_TOKEN }}`
5. Save credential

### Step 4: Update Workflow Nodes

After importing, you may need to:

1. **Update HTTP Request nodes**:
   - Select each HTTP Request node
   - Choose the credential you created
   - Verify URLs are correct

2. **Update Webhook URLs**:
   - For webhook workflows, copy the webhook URL
   - Configure your external services to send webhooks to this URL

3. **Test workflows**:
   - Click "Execute Workflow" to test
   - Check execution logs for errors
   - Fix any configuration issues

---

## 🔧 Configuration Details

### API Authentication

Your Laravel API uses Laravel Sanctum. To generate a token for n8n:

```bash
php artisan tinker
```

```php
$user = \App\Models\User::where('email', 'n8n@yourdomain.com')->first();
$token = $user->createToken('n8n-automation')->plainTextToken;
echo $token;
```

Use this token as `CITYPHONE_API_TOKEN`.

### Webhook Configuration

#### Payment Gateway Webhooks

Configure your payment gateways to send webhooks to:
```
https://your-n8n-instance.com/webhook/payment-webhook
```

Supported gateways:
- Amwal: `/webhooks/payment/amwal`
- Moyasar: `/webhooks/payment/moyasar`
- Tabby: `/webhooks/payment/tabby`
- Tamara: `/webhooks/payment/tamara`

#### OTO Shipping Webhook

Configure OTO dashboard to send webhooks to:
```
https://your-n8n-instance.com/webhook/oto-shipment-webhook
```

Then forward to Laravel:
```
https://yourdomain.com/api/webhooks/oto/shipment
```

---

## 📝 Workflow Customization

### Modifying Schedules

To change cron schedules, edit the Cron trigger node:

1. Open the workflow
2. Click on the Cron trigger node
3. Modify the schedule:
   - **Every X minutes**: `{ "field": "minutes", "minutesInterval": X }`
   - **Daily at specific time**: `{ "cronExpression": "0 9 * * *" }` (9 AM)
   - **Weekly**: `{ "cronExpression": "0 9 * * 1" }` (Monday 9 AM)

### Adding Email Templates

For email notifications, you'll need to set up email templates in your email service:

1. **Abandoned Cart Reminders**:
   - `abandoned_cart_reminder_1` (1 hour)
   - `abandoned_cart_reminder_2` (24 hours with discount)
   - `abandoned_cart_reminder_3` (48 hours final)

2. **Order Notifications**:
   - `order_confirmed`
   - `order_delivered`
   - `payment_expired`

3. **Reports**:
   - `daily_sales_report`

### Customizing Notifications

To add more notification channels:

1. Add new HTTP Request nodes
2. Configure for your service (Discord, Telegram, etc.)
3. Connect to appropriate workflow points

---

## 🧪 Testing Workflows

### Test Payment Webhook

```bash
curl -X POST https://your-n8n-instance.com/webhook/payment-webhook \
  -H "Content-Type: application/json" \
  -d '{
    "gateway": "amwal",
    "order_id": 123,
    "status": "paid",
    "amount": 100.00
  }'
```

### Test OTO Webhook

```bash
curl -X POST https://your-n8n-instance.com/webhook/oto-shipment-webhook \
  -H "Content-Type: application/json" \
  -H "X-OTO-Signature: your-signature" \
  -d '{
    "tracking_number": "TRACK123",
    "status": "delivered",
    "reference": "ORD-001"
  }'
```

### Test Scheduled Workflows

1. Click "Execute Workflow" manually
2. Check execution logs
3. Verify API calls are successful
4. Check database for updates

---

## 📊 Monitoring & Debugging

### View Execution History

1. Go to **Executions** in n8n sidebar
2. View all workflow executions
3. Click on any execution to see details
4. Check for errors or warnings

### Enable Error Notifications

Add error handling nodes to workflows:

1. Add **"On Error"** node after critical operations
2. Configure to send alerts to Slack/Email
3. Set up error monitoring

### Logging

n8n automatically logs:
- All workflow executions
- API request/response data
- Error messages
- Execution times

---

## 🔒 Security Considerations

1. **API Tokens**: Store securely in n8n environment variables
2. **Webhook Signatures**: Verify signatures in webhook handlers
3. **HTTPS**: Always use HTTPS for webhook endpoints
4. **Rate Limiting**: Configure rate limits in n8n
5. **Access Control**: Limit n8n access to authorized users only

---

## 🆘 Troubleshooting

### Common Issues

**Issue**: Workflow not executing
- **Solution**: Check if workflow is activated (toggle in top right)

**Issue**: API authentication failing
- **Solution**: Verify API token is correct and not expired

**Issue**: Webhook not receiving data
- **Solution**: Check webhook URL is correct and accessible

**Issue**: Cron not triggering
- **Solution**: Verify cron schedule syntax is correct

### Getting Help

1. Check n8n execution logs
2. Review Laravel application logs
3. Test API endpoints directly
4. Verify environment variables are set

---

## 📈 Next Steps

After setting up these workflows:

1. **Monitor Performance**: Track workflow execution times
2. **Optimize**: Adjust schedules based on usage patterns
3. **Expand**: Add more workflows based on your needs
4. **Integrate**: Connect with more external services

---

## 📄 License

These workflows are provided as-is for use with the CityPhone e-commerce platform.

---

**Last Updated**: January 2024
**n8n Version**: Compatible with n8n v1.0+
