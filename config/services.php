<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
    ],

    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'client_secret' => env('APPLE_CLIENT_SECRET'),
        'redirect' => env('APPLE_REDIRECT_URI'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OTO Shipping Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for OTO shipping integration. Requires API credentials
    | from your OTO account (https://tryoto.com).
    |
    | Required environment variables:
    | - OTO_API_KEY: Your OTO API key
    | - OTO_API_SECRET: Your OTO API secret
    | - OTO_ENVIRONMENT: 'sandbox', 'staging', or 'production' (staging maps to sandbox)
    | - OTO_WEBHOOK_SECRET: Secret for verifying webhook signatures
    |
    */

    'oto' => [
        'key' => env('OTO_API_KEY'),
        'secret' => env('OTO_API_SECRET'),
        'environment' => env('OTO_ENVIRONMENT', 'sandbox'),

        'urls' => [
            'base' => env('OTO_API_BASE_URL', 'https://api.tryoto.com/rest/v2'),
            'sandbox' => 'https://staging-api.tryoto.com/rest/v2',
            'production' => 'https://api.tryoto.com/rest/v2',
        ],

        // API endpoint paths
        'endpoints' => [
            'refresh_token' => env('OTO_ENDPOINT_REFRESH_TOKEN', '/refreshToken'),
            'create_order' => env('OTO_ENDPOINT_CREATE_ORDER', '/createOrder'),
            'create_shipment' => env('OTO_ENDPOINT_CREATE_SHIPMENT', '/createShipment'),
            'check_delivery' => env('OTO_ENDPOINT_CHECK_DELIVERY', '/shipmentTransactions'),
            'get_shipment' => env('OTO_ENDPOINT_GET_SHIPMENT', '/shipments'),
            'order_status' => env('OTO_ENDPOINT_ORDER_STATUS', '/orderStatus'),
            'track_shipment' => env('OTO_ENDPOINT_TRACK_SHIPMENT', '/trackShipment'),
            'cancel_order' => env('OTO_ENDPOINT_CANCEL_ORDER', '/orders/{id}/cancelOrder'),
        ],

        'webhook' => [
            'secret' => env('OTO_WEBHOOK_SECRET'),
            'signature_header' => env('OTO_WEBHOOK_SIGNATURE_HEADER', 'X-OTO-Signature'),
            'strict_verification' => env('OTO_WEBHOOK_STRICT_VERIFICATION', true),
        ],

        'timeout' => env('OTO_API_TIMEOUT', 30),
        'retry_times' => env('OTO_API_RETRY_TIMES', 2),
        'auto_complete_on_delivered' => env('OTO_AUTO_COMPLETE_ON_DELIVERED', true),

        // Default pickup location (ship-from address)
        'pickup' => [
            'name' => env('OTO_PICKUP_NAME', env('APP_NAME')),
            'phone' => env('OTO_PICKUP_PHONE', '99999999'),
            'email' => env('OTO_PICKUP_EMAIL', 'test@test.com'),
            'address' => env('OTO_PICKUP_ADDRESS', 'test address'),
            'city' => env('OTO_PICKUP_CITY', 'test city'),
            'country' => env('OTO_PICKUP_COUNTRY', 'SA'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for WhatsApp messaging integration.
    | Supports various WhatsApp providers (Twilio, WhatsApp Business API, etc.)
    |
    | Required environment variables:
    | - WHATSAPP_API_URL: Your WhatsApp API endpoint URL
    | - WHATSAPP_API_KEY: Your WhatsApp API key (if required)
    | - WHATSAPP_API_TOKEN: Your WhatsApp API token/bearer token
    |
    */

    'whatsapp' => [
        'api_url' => env('WHATSAPP_API_URL', env('HYPERSENDER_API_URL', 'https://app.hypersender.com')),
        'api_key' => env('WHATSAPP_API_KEY', env('HYPERSENDER_API_KEY')),
        'api_token' => env('WHATSAPP_API_TOKEN', env('HYPERSENDER_API_TOKEN')),
        'instance_id' => env('WHATSAPP_INSTANCE_ID', env('HYPERSENDER_INSTANCE_ID')),
        'provider' => env('WHATSAPP_PROVIDER', 'hypersender'),
        'endpoint' => env('WHATSAPP_ENDPOINT', '/api/whatsapp/v2/{instance}/send-text-safe'),
        'enabled' => env('WHATSAPP_ENABLED', env('HYPERSENDER_ENABLED', false)),
    ],

];
