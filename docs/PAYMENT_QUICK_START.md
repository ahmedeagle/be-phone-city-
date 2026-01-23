# Payment System Quick Start Guide

## 🚀 Quick Start (5 Minutes)

### Step 1: Configure Bank Account (Admin Panel)

1. Login to admin panel: `/admin`
2. Go to **Settings** (الإعدادات)
3. Add bank account details:
    - Bank Name (اسم البنك)
    - Account Holder (اسم صاحب الحساب)
    - Account Number (رقم الحساب)
    - IBAN (الآيبان)
    - Instructions (تعليمات)
4. Save

### Step 2: Test Cash Payment

```bash
# Create order via API
POST /api/v1/orders
{
  "payment_method_id": 1,  # Cash on Delivery
  "delivery_method": "home_delivery",
  "location_id": 123
}

# Response includes:
{
  "payment": {
    "status": "paid",  # Immediately paid for cash
    "gateway": "cash"
  }
}
```

### Step 3: Test Bank Transfer

```bash
# 1. Create order
POST /api/v1/orders
{
  "payment_method_id": 2,  # Bank Transfer
  ...
}

# Response includes bank account details
{
  "payment": {
    "status": "awaiting_review",
    "gateway": "bank_transfer",
    "bank_account_details": { ... },
    "upload_url": "/api/v1/orders/123/payment/upload-proof"
  }
}

# 2. Upload proof
POST /api/v1/orders/123/payment/upload-proof
Content-Type: multipart/form-data
{
  "payment_proof": <file>
}

# 3. Admin reviews in panel
# Navigate to: معاملات الدفع → View transaction → مراجعة
```

## 📝 Payment Method IDs

After seeding:

-   **1** = Cash on Delivery (active)
-   **2** = Bank Transfer (active)
-   **3** = Tamara (inactive)
-   **4** = Tabby (inactive)
-   **5** = Amwal (inactive)

## 🔧 Enable External Gateways (When Ready)

### Tamara

```env
# .env
TAMARA_ENABLED=true
TAMARA_API_URL=https://api-sandbox.tamara.co
TAMARA_API_TOKEN=your_token_here
TAMARA_MERCHANT_URL=https://yoursite.com
TAMARA_NOTIFICATION_URL=https://yoursite.com/webhooks/payment/tamara
```

Then in admin panel:

1. Go to **Payment Methods** (طرق الدفع)
2. Edit **Tamara**
3. Change status to **Active**

### Tabby & Amwal

Same process as Tamara with respective credentials.

## 📱 Frontend Integration

### Example: React/Vue

```javascript
// 1. Create order
const createOrder = async (orderData) => {
    const response = await axios.post("/api/v1/orders", orderData);
    const { order, payment } = response.data.data;

    // Handle different payment types
    switch (payment.gateway) {
        case "cash":
            // Show success message
            router.push(`/orders/${order.id}`);
            break;

        case "bank_transfer":
            // Show bank details and upload form
            showBankTransferFlow(payment);
            break;

        case "tamara":
        case "tabby":
        case "amwal":
            // Redirect to payment gateway
            window.location.href = payment.redirect_url;
            break;
    }
};

// 2. Upload bank transfer proof
const uploadProof = async (orderId, file) => {
    const formData = new FormData();
    formData.append("payment_proof", file);

    await axios.post(
        `/api/v1/orders/${orderId}/payment/upload-proof`,
        formData
    );

    // Show success - admin will review
    showMessage("تم رفع إثبات الدفع بنجاح. سيتم مراجعته قريباً");
};

// 3. Check payment status (polling)
const checkStatus = async (orderId) => {
    const response = await axios.get(
        `/api/v1/orders/${orderId}/payment/status`
    );

    return response.data.data;
};

// 4. Retry failed payment
const retryPayment = async (orderId) => {
    const response = await axios.post(
        `/api/v1/orders/${orderId}/payment/retry`
    );

    // Handle new payment session
    const { payment } = response.data.data;
    if (payment.redirect_url) {
        window.location.href = payment.redirect_url;
    }
};
```

## 🔐 Security Notes

1. **File Uploads:**

    - Max size: 10MB
    - Allowed: JPG, PNG, PDF
    - Stored in: `storage/app/payment-proofs/`

2. **Access Control:**

    - Users can only see their own orders
    - Admins can view all transactions
    - Payment proofs are not publicly accessible

3. **Webhooks:**
    - Signature validation enabled by default
    - Configure webhook URLs in gateway dashboards
    - Point to: `https://yoursite.com/webhooks/payment/{gateway}`

## 🐛 Troubleshooting

### Payment Not Working?

1. **Check payment method status:**

    ```bash
    php artisan tinker
    PaymentMethod::where('gateway', 'cash')->first()->status
    ```

2. **Check logs:**

    ```bash
    tail -f storage/logs/laravel.log | grep Payment
    ```

3. **Check migration:**
    ```bash
    php artisan migrate:status
    ```

### Bank Transfer Not Showing Account Details?

1. Configure in admin panel: Settings
2. Or check database:
    ```bash
    php artisan tinker
    Setting::getSettings()->bank_name
    ```

### File Upload Failing?

1. Check storage permissions:

    ```bash
    chmod -R 775 storage
    ```

2. Check file size in `config/payment-gateways.php`:
    ```php
    'max_file_size' => 10240, // 10MB
    ```

## 📊 Database Queries (Useful)

```bash
# In tinker (php artisan tinker)

# Get all pending payment reviews
PaymentTransaction::awaitingReview()->count();

# Get order payment status
Order::find(123)->payment_status;

# Get all bank transfer transactions
PaymentTransaction::where('gateway', 'bank_transfer')->get();

# Get transactions for an order
Order::find(123)->paymentTransactions;

# Mark expired payments (manual)
app(PaymentService::class)->markExpiredPayments();
```

## 🎯 Common Tasks

### Add New Payment Gateway

1. Create gateway class in `app/Services/PaymentGateways/`
2. Extend `AbstractPaymentGateway`
3. Implement required methods
4. Add to `PaymentGatewayFactory::make()`
5. Add configuration to `config/payment-gateways.php`
6. Seed payment method
7. Test!

### Change Payment Expiration Time

```php
// config/payment-gateways.php
'session' => [
    'expiration_minutes' => 30,  // Change this
    'max_retry_attempts' => 3,
    'retry_window_hours' => 24,
],
```

### Disable Proof Upload for Bank Transfer

```php
// config/payment-gateways.php
'bank_transfer' => [
    'enabled' => false,  // Disable entirely
],
```

## 📧 Email Notifications (TODO)

When ready, add email notifications:

```php
// In PaymentService::reviewPaymentProof()
if ($approve) {
    Mail::to($order->user->email)->send(
        new PaymentApproved($order)
    );
} else {
    Mail::to($order->user->email)->send(
        new PaymentRejected($order, $notes)
    );
}
```

## 🔄 Scheduled Tasks

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Mark expired payments every 5 minutes
    $schedule->call(function () {
        app(PaymentService::class)->markExpiredPayments();
    })->everyFiveMinutes();
}
```

Then run:

```bash
php artisan schedule:work  # Development
# Or in production, add to crontab:
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## 🎉 You're Ready!

The system is fully operational for:

-   ✅ Cash on Delivery
-   ✅ Bank Transfers with admin review
-   📝 External gateways (when configured)

**Next Steps:**

1. Test order creation with each payment method
2. Configure bank account details
3. Test proof upload and review workflow
4. When ready, add Tamara/Tabby/Amwal credentials
5. Test with real transactions in sandbox mode
6. Deploy to production!

---

**Need Help?** Check `docs/PAYMENT_SYSTEM_IMPLEMENTATION_SUMMARY.md` for detailed documentation.
