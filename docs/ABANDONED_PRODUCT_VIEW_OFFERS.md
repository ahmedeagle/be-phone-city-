# Abandoned Product View Offers via WhatsApp

This feature automatically sends WhatsApp offers to users who viewed products but didn't add them to cart or purchase them.

## Overview

When an authenticated user views a product:
1. The system tracks the view in the `product_views` table
2. After 1-2 hours (configurable), if the user hasn't added the product to cart or ordered it, they receive a WhatsApp message
3. If there's an active offer for the product, it's included in the message
4. If no offer exists, a reminder message is sent

## Components

### 1. Database

**Migration**: `database/migrations/2026_01_28_123727_create_product_views_table.php`

**Table**: `product_views`
- `user_id`: User who viewed the product
- `product_id`: Product that was viewed
- `viewed_at`: Timestamp of the view
- `offer_sent`: Boolean flag if offer was sent
- `offer_sent_at`: Timestamp when offer was sent
- `purchased`: Boolean flag if user purchased the product

### 2. Models

**ProductView Model** (`app/Models/ProductView.php`)
- Tracks product views for authenticated users
- Relationships: `user()`, `product()`
- Scopes: `pendingOffer()`, `notPurchased()`
- Methods: `markOfferSent()`, `markAsPurchased()`

### 3. Controller

**ProductController** (`app/Http/Controllers/Api/V1/ProductController.php`)
- `show()` method tracks views for authenticated users
- Uses `ProductView::updateOrCreate()` to record views

### 4. Services

**WhatsAppService** (`app/Services/WhatsAppService.php`)
- `sendMessage()`: Sends WhatsApp message to user
- `sendProductOffer()`: Sends product offer message
- `buildOfferMessage()`: Builds localized message (Arabic/English)
- `formatPhoneNumber()`: Formats phone numbers for WhatsApp API

### 5. Jobs

**SendAbandonedProductViewOffer** (`app/Jobs/SendAbandonedProductViewOffer.php`)
- Queued job that sends WhatsApp offers
- Checks if user purchased or added to cart before sending
- Marks offer as sent after successful delivery

### 6. Commands

**SendAbandonedProductViewOffers** (`app/Console/Commands/SendAbandonedProductViewOffers.php`)
- Scheduled command: `product-views:send-offers`
- Options:
  - `--hours=1`: Number of hours after view to send offer (default: 1)
  - `--dry-run`: Test without sending messages

### 7. Observers

**OrderObserver** (`app/Observers/OrderObserver.php`)
- Automatically marks product views as purchased when orders are created
- Prevents sending offers for products that were purchased

## Configuration

### Environment Variables

Add to your `.env` file:

```env
# Hypersender WhatsApp API Configuration
HYPERSENDER_API_URL=https://app.hypersender.com
HYPERSENDER_API_TOKEN=your_bearer_token_here
HYPERSENDER_INSTANCE_ID=your_instance_id_here
HYPERSENDER_ENABLED=true

# Alternative: Use generic WhatsApp config (falls back to Hypersender)
WHATSAPP_API_URL=https://app.hypersender.com
WHATSAPP_API_TOKEN=your_bearer_token_here
WHATSAPP_INSTANCE_ID=your_instance_id_here
WHATSAPP_PROVIDER=hypersender
WHATSAPP_ENABLED=true

# Frontend URL for product links in messages
FRONTEND_URL=https://your-frontend-domain.com
```

**Note**: The service supports both `HYPERSENDER_*` and `WHATSAPP_*` environment variables. Hypersender variables take precedence.

### Service Configuration

The WhatsApp service configuration is in `config/services.php`:

```php
'whatsapp' => [
    'api_url' => env('WHATSAPP_API_URL', env('HYPERSENDER_API_URL')),
    'api_key' => env('WHATSAPP_API_KEY', env('HYPERSENDER_API_KEY')),
    'api_token' => env('WHATSAPP_API_TOKEN', env('HYPERSENDER_API_TOKEN')),
    'provider' => env('WHATSAPP_PROVIDER', 'hypersender'),
    'endpoint' => env('WHATSAPP_ENDPOINT', '/send'),
    'enabled' => env('WHATSAPP_ENABLED', env('HYPERSENDER_ENABLED', false)),
],
```

**Hypersender Integration**: The service is configured to work with Hypersender by default. Adjust the `endpoint` configuration based on Hypersender's API documentation.

## Scheduling

The feature is automatically scheduled in `bootstrap/app.php`:

```php
// Send abandoned product view offers every hour
$schedule->command('product-views:send-offers --hours=1')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Also check for 2-hour old views
$schedule->command('product-views:send-offers --hours=2')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();
```

**Note**: Make sure your Laravel scheduler is running:
```bash
# Add to crontab
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## How It Works

### 1. Product View Tracking

When an authenticated user views a product (`GET /api/v1/products/{product}`):
- System creates/updates a `ProductView` record
- `viewed_at` timestamp is set to current time

### 2. Offer Sending Process

Every hour, the scheduled command runs:
1. Finds product views older than 1-2 hours that:
   - Haven't received an offer (`offer_sent = false`)
   - Haven't been purchased (`purchased = false`)
2. For each view:
   - Checks if user added product to cart
   - Checks if user ordered the product
   - If purchased: Marks view as purchased
   - If not purchased: Dispatches `SendAbandonedProductViewOffer` job

### 3. WhatsApp Message

The job sends a WhatsApp message:
- **With Offer**: Includes offer details, discount, and final price
- **Without Offer**: Simple reminder with product name and price
- Messages are localized (Arabic/English) based on app locale
- Includes product URL for easy access

### 4. Purchase Detection

Product views are automatically marked as purchased when:
- User creates an order containing the product (via `OrderObserver`)
- User adds product to cart (checked before sending offer)

## Message Examples

### With Offer (English)
```
Hello John 👋

We noticed you were interested in: iPhone 15 Pro

🎉 We have a special offer for you!
Offer: Summer Sale
Discount: 10%
Final Price: 4,500.00 SAR

Don't miss this opportunity! 🛒
https://yourdomain.com/products/iphone-15-pro
```

### Without Offer (Arabic)
```
مرحباً أحمد 👋

لاحظنا أنك كنت مهتماً بمنتج: iPhone 15 Pro
السعر: 5,000.00 ريال

هل ما زلت مهتماً؟ 🛒
https://yourdomain.com/products/iphone-15-pro
```

## Manual Testing

### Test View Tracking
```bash
# View a product as authenticated user
curl -H "Authorization: Bearer YOUR_TOKEN" \
  https://yourdomain.com/api/v1/products/1
```

### Test Command (Dry Run)
```bash
# Check what would be sent without actually sending
php artisan product-views:send-offers --hours=1 --dry-run
```

### Test Command (Actual)
```bash
# Send offers for views older than 1 hour
php artisan product-views:send-offers --hours=1
```

### Test Job Manually
```php
// In tinker
$productView = \App\Models\ProductView::first();
\App\Jobs\SendAbandonedProductViewOffer::dispatch($productView);
```

## Hypersender Integration

The `WhatsAppService` is configured to work with **Hypersender** by default. The service uses Hypersender's REST API to send WhatsApp messages.

### Hypersender API Configuration

1. **Get Hypersender Credentials**:
   - Sign up at Hypersender and get your API key/token
   - Note your API endpoint URL (usually `https://api.hypersender.io`)

2. **Configure Environment Variables**:
   ```env
   HYPERSENDER_API_URL=https://api.hypersender.io
   HYPERSENDER_API_KEY=your_api_key_here
   HYPERSENDER_ENABLED=true
   ```

3. **API Endpoint**:
   - Default endpoint: `/send`
   - Adjust via `WHATSAPP_ENDPOINT` if Hypersender uses a different path
   - Common alternatives: `/messages`, `/whatsapp/send`, `/api/send`

### Hypersender API Request Format

The service sends requests in this format:
```php
POST https://api.hypersender.io/send
Headers:
  Content-Type: application/json
  Authorization: Bearer {api_token}
  // OR
  X-API-Key: {api_key}

Body:
{
  "to": "966501234567",
  "message": "Your message text here",
  "type": "text"
}
```

### Adjusting for Hypersender's Specific API

If Hypersender uses different field names, update `sendViaHypersender()` in `WhatsAppService.php`:

```php
// Example: If Hypersender uses 'phone' instead of 'to'
$payload = [
    'phone' => $phone,  // Instead of 'to'
    'text' => $message, // Instead of 'message'
];
```

### Testing Hypersender Integration

```bash
# Test in tinker
php artisan tinker

$user = \App\Models\User::find(1);
$product = \App\Models\Product::find(1);
app(\App\Services\WhatsAppService::class)->sendProductOffer($user, $product);
```

### Other WhatsApp Providers

If you need to use a different provider (Twilio, WhatsApp Business API, etc.), you can modify the `sendViaHypersender()` method or create provider-specific methods.

## Monitoring

### Check Product Views
```php
// Views pending offers
\App\Models\ProductView::pendingOffer()->count();

// Views that received offers
\App\Models\ProductView::where('offer_sent', true)->count();

// Views that resulted in purchases
\App\Models\ProductView::where('purchased', true)->count();
```

### Logs

The system logs important events:
- WhatsApp message sent successfully
- Failed WhatsApp messages
- Skipped offers (user purchased)
- Product views marked as purchased

Check logs in `storage/logs/laravel.log`

## Troubleshooting

### Offers Not Sending

1. **Check WhatsApp Configuration**
   ```bash
   php artisan tinker
   config('services.whatsapp')
   ```

2. **Check Queue Workers**
   ```bash
   # Make sure queue workers are running
   php artisan queue:work
   ```

3. **Check Scheduler**
   ```bash
   # Test scheduler manually
   php artisan schedule:run
   ```

4. **Check Product Views**
   ```bash
   php artisan tinker
   \App\Models\ProductView::pendingOffer()->count();
   ```

### Messages Not Received

1. **Verify Phone Number Format**
   - Phone numbers should be in format: `966XXXXXXXXX` (12 digits)
   - The service automatically formats phone numbers

2. **Check WhatsApp API Response**
   - Check logs for API errors
   - Verify API credentials are correct

3. **Test Manually**
   ```php
   $user = \App\Models\User::find(1);
   $product = \App\Models\Product::find(1);
   app(\App\Services\WhatsAppService::class)->sendProductOffer($user, $product);
   ```

## Future Enhancements

- Email fallback if WhatsApp fails
- Configurable delay times per product/category
- A/B testing for message content
- Analytics dashboard for conversion rates
- Support for multiple WhatsApp providers
- Rate limiting to prevent spam

## Security Considerations

- Only authenticated users' views are tracked
- Phone numbers are validated and formatted securely
- API credentials stored in environment variables
- Failed messages are logged but don't expose sensitive data
- Users can't be spammed (one offer per view)
