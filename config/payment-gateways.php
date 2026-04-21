<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | This option defines the default payment gateway to use when none is
    | specified. You can change this to any supported gateway.
    |
    */
    'default_gateway' => env('DEFAULT_PAYMENT_GATEWAY', 'cash'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configurations
    |--------------------------------------------------------------------------
    |
    | Here you can configure the settings for each payment gateway.
    | Set 'enabled' to true to activate a gateway.
    |
    */
    'gateways' => [

        /*
        |--------------------------------------------------------------------------
        | Tamara Payment Gateway
        |--------------------------------------------------------------------------
        |
        | Tamara is a buy-now-pay-later service popular in Saudi Arabia and UAE.
        |
        | Documentation: https://docs.tamara.co
        |
        */
        'tamara' => [
            'enabled' => env('TAMARA_ENABLED', false),
            'api_url' => env('TAMARA_API_URL', 'https://api-sandbox.tamara.co'),
            'api_token' => env('TAMARA_API_TOKEN'),
            'merchant_url' => env('TAMARA_MERCHANT_URL'),
            'notification_url' => env('TAMARA_NOTIFICATION_URL'),
            'webhook_token' => env('TAMARA_WEBHOOK_TOKEN'),
            'timeout' => 30, // API request timeout in seconds
        ],

        /*
        |--------------------------------------------------------------------------
        | Tabby Payment Gateway
        |--------------------------------------------------------------------------
        |
        | Tabby is a flexible payment solution offering installment plans.
        |
        | Documentation: https://docs.tabby.ai
        |
        */
        'tabby' => [
            'enabled' => env('TABBY_ENABLED', false),
            'api_url' => env('TABBY_API_URL', 'https://api.tabby.ai'),
            'public_key' => env('TABBY_PUBLIC_KEY'),
            'secret_key' => env('TABBY_SECRET_KEY'),
            'merchant_code' => env('TABBY_MERCHANT_CODE'),
            'timeout' => 30,
        ],

        /*
        |--------------------------------------------------------------------------
        | Amwal Payment Gateway
        |--------------------------------------------------------------------------
        |
        | Amwal provides fast and secure payment processing.
        |
        | Documentation: https://docs.amwal.tech
        |
        */
        'amwal' => [
            'enabled' => env('AMWAL_ENABLED', false),
            'api_url' => env('AMWAL_API_URL', 'https://backend.sa.amwal.tech'),
            'merchant_id' => env('AMWAL_MERCHANT_ID'),
            'api_key' => env('AMWAL_API_KEY'),
            'amwal_key' => env('AMWAL_KEY'), // Optional: for environment identification (sandbox-amwal-xxx or prod-amwal-xxx)
            'webhook_secret' => env('AMWAL_WEBHOOK_SECRET'), // Optional: for webhook signature validation
            'webhook_url' => env('AMWAL_WEBHOOK_URL'), // Optional: custom HTTPS webhook URL (for server-to-server notifications)
            'callback_url' => env('AMWAL_CALLBACK_URL'), // Optional: custom HTTPS callback URL (for user redirect after payment)
            'timeout' => 30,
        ],

        /*
        |--------------------------------------------------------------------------
        | Moyasar Payment Gateway
        |--------------------------------------------------------------------------
        |
        | Moyasar is a payment gateway service provider in Saudi Arabia.
        | Supports credit cards, Apple Pay, STC Pay, and other payment methods.
        |
        | Documentation: https://moyasar.com/docs
        |
        */
        'moyasar' => [
            'enabled' => env('MOYASAR_ENABLED', false),
            'api_url' => env('MOYASAR_API_URL', 'https://api.moyasar.com/v1'),
            'secret_key' => env('MOYASAR_SECRET_KEY'),
            'publishable_key' => env('MOYASAR_PUBLISHABLE_KEY'),
            'webhook_secret' => env('MOYASAR_WEBHOOK_SECRET'),
            'callback_url' => env('MOYASAR_CALLBACK_URL'),
            'default_source' => env('MOYASAR_DEFAULT_SOURCE', 'creditcard'), // creditcard, applepay, stcpay, etc.
            'timeout' => 30,
        ],

        /*
        |--------------------------------------------------------------------------
        | Emkan Payment Gateway
        |--------------------------------------------------------------------------
        |
        | Emkan provides Sharia-compliant Buy-Now-Pay-Later financing in Saudi Arabia.
        |
        | Documentation: https://emkanfinance.com.sa
        |
        */
        'emkan' => [
            'enabled' => env('EMKAN_ENABLED', false),
            'api_url' => env('EMKAN_API_URL', 'https://api.emkanfinance.com.sa'),
            'merchant_id' => env('EMKAN_MERCHANT_ID'),
            'api_key' => env('EMKAN_API_KEY'),
            'webhook_secret' => env('EMKAN_WEBHOOK_SECRET'),
            'timeout' => 30,
        ],

        /*
        |--------------------------------------------------------------------------
        | Madfu Payment Gateway
        |--------------------------------------------------------------------------
        |
        | Madfu offers split payments (Buy-Now-Pay-Later) in Saudi Arabia.
        |
        | Documentation: https://www.madfu.com.sa
        |
        */
        'madfu' => [
            'enabled' => env('MADFU_ENABLED', false),
            'api_url' => env('MADFU_API_URL', 'https://api.madfu.com.sa'),
            'merchant_id' => env('MADFU_MERCHANT_ID'),
            'app_code' => env('MADFU_APP_CODE'),
            'api_key' => env('MADFU_API_KEY'),
            'basic_auth' => env('MADFU_BASIC_AUTH'), // Pre-built Basic auth token from Madfu portal (without 'Basic ' prefix)
            'platform_type_id' => env('MADFU_PLATFORM_TYPE_ID', 7), // 7 = Web per Madfu API spec
            'branch_id' => env('MADFU_BRANCH_ID', 1),
            'webhook_secret' => env('MADFU_WEBHOOK_SECRET'),
            'timeout' => 30,
        ],

        /*
        |--------------------------------------------------------------------------
        | Cash Payment
        |--------------------------------------------------------------------------
        |
        | Traditional cash on delivery payment method.
        |
        */
        'cash' => [
            'enabled' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | Bank Transfer Payment
        |--------------------------------------------------------------------------
        |
        | Bank transfer requires customers to upload payment proof for admin review.
        |
        */
        'bank_transfer' => [
            'enabled' => env('BANK_TRANSFER_ENABLED', true),
            'auto_approve' => env('BANK_TRANSFER_AUTO_APPROVE', false),
            'allowed_file_types' => ['jpg', 'jpeg', 'png', 'pdf'],
            'max_file_size' => 10240, // 10MB in KB
            'storage_path' => 'payment-proofs', // Storage path in storage/app
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Session Settings
    |--------------------------------------------------------------------------
    |
    | Global settings for payment sessions
    |
    */
    'session' => [
        'expiration_minutes' => 30, // Payment session expiration time
        'max_retry_attempts' => 3,  // Maximum payment retry attempts per order
        'retry_window_hours' => 24, // Time window to allow retries
        'frontend_redirect_url' => env('PAYMENT_FRONTEND_REDIRECT_URL', 'http://localhost:3000/orders/{order_id}/status'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Settings
    |--------------------------------------------------------------------------
    |
    | Default currency for payments
    |
    */
    'currency' => env('PAYMENT_CURRENCY', 'SAR'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Security
    |--------------------------------------------------------------------------
    |
    | Security settings for payment webhooks
    |
    */
    'webhook' => [
        'verify_signature' => env('WEBHOOK_VERIFY_SIGNATURE', true),
        'allowed_ips' => [], // Optional: Restrict webhook IPs
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable detailed logging for payment transactions
    |
    */
    'logging' => [
        'enabled' => env('PAYMENT_LOGGING_ENABLED', true),
        'channel' => env('PAYMENT_LOG_CHANNEL', 'stack'),
        'log_request' => true,  // Log payment requests
        'log_response' => true, // Log payment responses
    ],
];
