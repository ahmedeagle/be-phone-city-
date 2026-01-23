# OTO Shipping Configuration

This document outlines the required environment variables for OTO shipping integration.

## Required Environment Variables

Add the following variables to your `.env` file:

```bash
# OTO API Credentials
OTO_API_KEY=your_oto_api_key_here
OTO_API_SECRET=your_oto_api_secret_here
OTO_ENVIRONMENT=sandbox  # Options: 'sandbox', 'staging', or 'production' (staging uses sandbox URLs)

# OTO Webhook Configuration
OTO_WEBHOOK_SECRET=your_webhook_secret_here
OTO_WEBHOOK_SIGNATURE_HEADER=X-OTO-Signature
OTO_WEBHOOK_STRICT_VERIFICATION=true

# OTO API Settings
OTO_API_BASE_URL=https://apis.tryoto.com
OTO_API_TIMEOUT=30
OTO_API_RETRY_TIMES=2

# Automatic order completion when delivered (optional)
OTO_AUTO_COMPLETE_ON_DELIVERED=false

# Default Pickup Location (Ship-From Address)
OTO_PICKUP_NAME="Your Store Name"
OTO_PICKUP_PHONE="+966XXXXXXXXX"
OTO_PICKUP_EMAIL="warehouse@yourstore.com"
OTO_PICKUP_ADDRESS="Your warehouse street address"
OTO_PICKUP_CITY="Riyadh"
OTO_PICKUP_COUNTRY=SA
```

## Configuration Details

### API Credentials

-   **OTO_API_KEY**: Your OTO Refresh Token (v2 API). This is used to obtain a temporary Access Token.
-   **OTO_API_SECRET**: Your OTO API Secret (if provided by OTO).
-   **OTO_ENVIRONMENT**: Use `sandbox` or `staging` for testing, `production` for live orders.
-   **OTO_API_BASE_URL**: The base URL for the OTO API (default: `https://api.tryoto.com/rest/v2`).

### Webhook Settings

-   **OTO_WEBHOOK_SECRET**: Shared secret for webhook signature verification
-   **OTO_WEBHOOK_SIGNATURE_HEADER**: Header name containing the webhook signature (default: `X-OTO-Signature`)
-   **OTO_WEBHOOK_STRICT_VERIFICATION**: Enable/disable strict signature verification

### Pickup Location

Configure your default warehouse/pickup location. This information is sent to OTO when creating shipments.

## Testing

In sandbox or staging mode, you can test the integration without creating real shipments. Make sure to:

1. Set `OTO_ENVIRONMENT=sandbox` or `OTO_ENVIRONMENT=staging`
2. Use sandbox/staging API credentials
3. Test webhook endpoints using OTO's webhook testing tools

## Security Notes

-   Never commit `.env` file to version control
-   Rotate API keys periodically
-   Use strong webhook secrets
-   Enable strict webhook verification in production

## Webhook Endpoint

After deployment, register the following webhook URL with OTO:

```
https://yourdomain.com/api/webhooks/oto/shipment
```

## Support

For OTO API documentation, visit: https://apis.tryoto.com
