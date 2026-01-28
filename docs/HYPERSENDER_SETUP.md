# Hypersender WhatsApp Integration Setup

Quick guide to configure Hypersender for sending WhatsApp messages in the abandoned product view offers feature.

## Prerequisites

1. Hypersender account with API access
2. Hypersender API credentials (API Key or Token)
3. Hypersender API endpoint URL

## Configuration Steps

### 1. Get Hypersender Credentials

1. Log in to your Hypersender dashboard
2. Navigate to API Settings or Developer section
3. Copy your:
   - API URL (e.g., `https://api.hypersender.io`)
   - API Key or API Token
   - API Endpoint path (usually `/send` or `/messages`)

### 2. Configure Environment Variables

Add to your `.env` file:

```env
# Hypersender Configuration
HYPERSENDER_API_URL=https://app.hypersender.com
HYPERSENDER_API_TOKEN=your_bearer_token_here
HYPERSENDER_INSTANCE_ID=your_instance_id_here
HYPERSENDER_ENABLED=true

# Alternative: Use generic WhatsApp config
WHATSAPP_API_URL=https://app.hypersender.com
WHATSAPP_API_TOKEN=your_bearer_token_here
WHATSAPP_INSTANCE_ID=your_instance_id_here
WHATSAPP_ENABLED=true
```

**Important**: 
- Hypersender requires a **Bearer token** (not API key)
- You need your **Instance ID** from Hypersender dashboard
- Phone numbers are automatically formatted for chatId (phone + @c.us)

### 3. Verify Configuration

Check your configuration:

```bash
php artisan tinker
```

```php
config('services.whatsapp')
```

Should return:
```php
[
    "api_url" => "https://api.hypersender.io",
    "api_key" => "your_api_key_here",
    "provider" => "hypersender",
    "endpoint" => "/send",
    "enabled" => true,
]
```

## Testing

### Test Message Sending

```bash
php artisan tinker
```

```php
$user = \App\Models\User::find(1); // User with valid phone number
$product = \App\Models\Product::find(1);

// Send test offer message
app(\App\Services\WhatsAppService::class)->sendProductOffer($user, $product);
```

### Test Scheduled Command

```bash
# Dry run (won't send actual messages)
php artisan product-views:send-offers --hours=1 --dry-run

# Actual run
php artisan product-views:send-offers --hours=1
```

## Hypersender API Format

The service sends requests to Hypersender using their official API format:

**Endpoint**: `POST https://app.hypersender.com/api/whatsapp/v2/{instance_id}/send-text-safe`

**Headers**:
```
Content-Type: application/json
Authorization: Bearer {api_token}
```

**Request Body**:
```json
{
  "chatId": "1558125032@c.us",
  "text": "Hello! We have a special offer for you..."
}
```

**Note**: 
- `chatId` format: phone number (without country code) + `@c.us`
- Phone numbers are automatically formatted (supports Saudi Arabia 966 and Egypt 20)
- Example: Egyptian number `201558125032` becomes `1558125032@c.us`

**Response** (Success):
```json
{
  "success": true,
  "message_id": "msg_123456",
  "status": "sent"
}
```

## Phone Number Support

The service supports multiple country codes:

### Saudi Arabia (966)
- Formats: `966501234567`, `0501234567`, `501234567`
- Converts to chatId: `501234567@c.us`

### Egypt (20)
- Formats: `201558125032`, `01558125032`, `1558125032`
- Converts to chatId: `1558125032@c.us`

Phone numbers are automatically detected and formatted correctly.

## Customizing for Hypersender's API

If Hypersender uses different field names or structure, update `app/Services/WhatsAppService.php`:

### Example: Different Field Names

If Hypersender expects `phone` instead of `to`:

```php
protected function sendViaHypersender(...)
{
    $payload = [
        'phone' => $phone,      // Changed from 'to'
        'text' => $message,     // Changed from 'message'
        'type' => 'text',
    ];
    // ... rest of the code
}
```

### Example: Different Authentication

If Hypersender uses query parameters:

```php
$apiUrl = rtrim($apiUrl, '/');
$endpoint = $endpoint . '?api_key=' . $apiKey;

$response = Http::withHeaders($headers)
    ->post($apiUrl . $endpoint, $payload);
```

### Example: Different Endpoint

If Hypersender uses `/api/v1/messages`:

```env
WHATSAPP_ENDPOINT=/api/v1/messages
```

## Troubleshooting

### Messages Not Sending

1. **Check API Credentials**:
   ```bash
   php artisan tinker
   config('services.whatsapp.api_token')
   config('services.whatsapp.instance_id')
   config('services.whatsapp.api_url')
   ```

2. **Check Logs**:
   ```bash
   tail -f storage/logs/laravel.log | grep -i "hypersender\|whatsapp"
   ```

3. **Test API Connection**:
   ```bash
   # Replace {instance_id} with your actual instance ID
   curl -X POST https://app.hypersender.com/api/whatsapp/v2/{instance_id}/send-text-safe \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"chatId":"1558125032@c.us","text":"Test message"}'
   ```
   
   **Note**: Use phone number without country code + `@c.us` for chatId

### Common Issues

**Issue**: "API configuration missing"
- **Solution**: Check that `HYPERSENDER_API_URL`, `HYPERSENDER_API_TOKEN`, and `HYPERSENDER_INSTANCE_ID` are set in `.env`

**Issue**: "Instance ID is required"
- **Solution**: Get your Instance ID from Hypersender dashboard and set `HYPERSENDER_INSTANCE_ID` in `.env`

**Issue**: "Invalid phone number"
- **Solution**: Phone numbers must be in format `966XXXXXXXXX` (12 digits). The service auto-formats them.

**Issue**: "401 Unauthorized"
- **Solution**: Verify your API key/token is correct and has proper permissions

**Issue**: "404 Not Found"
- **Solution**: Check the endpoint path. Update `WHATSAPP_ENDPOINT` if needed.

## Rate Limits

Check Hypersender's rate limits and adjust accordingly:

- Default: Messages are queued via Laravel's queue system
- Adjust queue workers: `php artisan queue:work --tries=3`
- Monitor queue: `php artisan queue:monitor`

## Monitoring

### Check Sent Messages

```php
// In tinker
\App\Models\ProductView::where('offer_sent', true)->count();
```

### Check Failed Messages

Check logs for errors:
```bash
grep "Hypersender API error" storage/logs/laravel.log
```

## Support

- Hypersender Documentation: Check Hypersender's official API docs
- Laravel Logs: `storage/logs/laravel.log`
- Queue Jobs: `php artisan queue:work --verbose`
