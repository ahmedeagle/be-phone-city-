# Payment Gateway Integration Request

## Context

I have a Laravel e-commerce application with an existing order system. I need to integrate multiple payment gateways (Tamara, Tabby, Amwal) with support for adding more gateways in the future, plus a cash payment option.

## Current System Architecture

### Models

-   **User**: Handles authentication and relationships
-   **Order**: Main order model with statuses (in_progress, completed, cancelled)
-   **OrderItem**: Individual items in orders
-   **PaymentMethod**: Stores payment methods with processing fees
-   **Location**: Customer shipping addresses
-   **City**: Cities with shipping fees
-   **Discount**: Discount codes with various conditions
-   **Invoice**: Order invoices with PDF generation
-   **Point**: Customer loyalty points system

### Services

-   **OrderService**: Creates orders from cart
-   **OrderCalculationService**: Calculates totals, taxes, fees
-   **ShippingService**: Handles shipping calculations
-   **DiscountService**: Validates and calculates discounts
-   **PointsService**: Manages customer points

### Current Order Flow

1. User adds items to cart
2. `OrderController::preview()` calculates order totals
3. `OrderController::store()` creates order with all calculations
4. Order automatically creates invoice
5. Points awarded/consumed as configured

## Requirements

### 1. Payment Gateway Structure

Create a **flexible, extensible payment gateway system** that:

-   Supports multiple payment providers (Tamara, Tabby, Amwal)
-   Easily allows adding new gateways
-   Handles cash payments
-   **Handles bank transfer with payment proof upload**
-   Manages payment callbacks/webhooks
-   Stores transaction records
-   Updates order status based on payment status
-   **Admin review and approval system for bank transfers**

### 2. Required Components

#### A. Database Migrations

Create migrations for:

-   `payment_transactions` table (id, order_id, payment_method_id, gateway, transaction_id, amount, status, payload, response, **payment_proof_path**, **reviewed_by**, **reviewed_at**, **review_notes**, created_at, updated_at)
-   Add `payment_gateway` column to `payment_methods` table (values: 'cash', 'bank_transfer', 'tamara', 'tabby', 'amwal')
-   Add `payment_status` column to `orders` table (pending, **awaiting_review**, processing, paid, failed, refunded)
-   Add `payment_transaction_id` column to `orders` table

#### B. Configuration Files

Create `config/payment-gateways.php`:

```php
return [
    'default_gateway' => env('DEFAULT_PAYMENT_GATEWAY', 'cash'),

    'gateways' => [
        'tamara' => [
            'enabled' => env('TAMARA_ENABLED', false),
            'api_url' => env('TAMARA_API_URL'),
            'api_token' => env('TAMARA_API_TOKEN'),
            'merchant_url' => env('TAMARA_MERCHANT_URL'),
            'notification_url' => env('TAMARA_NOTIFICATION_URL'),
            'webhook_token' => env('TAMARA_WEBHOOK_TOKEN'),
        ],
        'tabby' => [
            'enabled' => env('TABBY_ENABLED', false),
            'api_url' => env('TABBY_API_URL'),
            'public_key' => env('TABBY_PUBLIC_KEY'),
            'secret_key' => env('TABBY_SECRET_KEY'),
            'merchant_code' => env('TABBY_MERCHANT_CODE'),
        ],
        'amwal' => [
            'enabled' => env('AMWAL_ENABLED', false),
            'api_url' => env('AMWAL_API_URL'),
            'merchant_id' => env('AMWAL_MERCHANT_ID'),
            'api_key' => env('AMWAL_API_KEY'),
        ],
        'cash' => [
            'enabled' => true,
        ],
        'bank_transfer' => [
            'enabled' => env('BANK_TRANSFER_ENABLED', true),
            'auto_approve' => env('BANK_TRANSFER_AUTO_APPROVE', false),
            'allowed_file_types' => ['jpg', 'jpeg', 'png', 'pdf'],
            'max_file_size' => 10240, // 10MB in KB
        ],
    ],
];
```

#### C. Abstract Payment Gateway Class

Create `app/Services/PaymentGateways/AbstractPaymentGateway.php`:

```php
abstract class AbstractPaymentGateway
{
    abstract public function createPayment(Order $order): array;
    abstract public function capturePayment(string $transactionId): array;
    abstract public function refundPayment(string $transactionId, float $amount): array;
    abstract public function getPaymentStatus(string $transactionId): array;
    abstract public function handleWebhook(array $payload): array;
    abstract public function requiresProofUpload(): bool;
    abstract public function requiresAdminReview(): bool;
}
```

#### D. Gateway Implementations

Create separate classes for each gateway:

-   `app/Services/PaymentGateways/TamaraGateway.php`
-   `app/Services/PaymentGateways/TabbyGateway.php`
-   `app/Services/PaymentGateways/AmwalGateway.php`
-   `app/Services/PaymentGateways/CashGateway.php`
-   `app/Services/PaymentGateways/BankTransferGateway.php`

Each should extend `AbstractPaymentGateway` and implement all methods.

#### E. Payment Gateway Factory

Create `app/Services/PaymentGateways/PaymentGatewayFactory.php`:

```php
class PaymentGatewayFactory
{
    public static function make(string $gateway): AbstractPaymentGateway
    {
        return match($gateway) {
            'tamara' => new TamaraGateway(),
            'tabby' => new TabbyGateway(),
            'amwal' => new AmwalGateway(),
            'cash' => new CashGateway(),
            'bank_transfer' => new BankTransferGateway(),
            default => throw new Exception("Gateway {$gateway} not supported"),
        };
    }
}
```

#### F. Payment Service

Create `app/Services/PaymentService.php` to orchestrate payment operations:

-   `initiatePayment(Order $order)`
-   `processPaymentCallback(Order $order, array $data)`
-   `handleWebhook(string $gateway, array $payload)`
-   `refundOrder(Order $order, ?float $amount = null)`
-   `uploadPaymentProof(Order $order, UploadedFile $file): string`
-   `reviewPaymentProof(Order $order, bool $approve, ?string $notes = null)`
-   `getBankAccountDetails(): array`

#### G. Payment Transaction Model

Create `app/Models/PaymentTransaction.php` to store all payment records.

#### H. Controllers

Update `OrderController`:

-   Modify `store()` to initiate payment after order creation
-   Add payment status in response
-   Return payment URL/data for frontend
-   Return bank account details if payment method is bank transfer from setting or (we well add dutails letter)

Create `app/Http/Controllers/Api/V1/PaymentController.php`:

-   `callback(Request $request)` - Handle return from payment gateway
-   `webhook(Request $request, string $gateway)` - Handle webhook notifications
-   `status(Order $order)` - Check payment status
-   **`uploadProof(Request $request, Order $order)` - Upload bank transfer proof**
-   **`bankAccountDetails()` - Get bank account info for customer**

#### I. Routes

Add to `routes/api.php`:

```php
Route::prefix('v1')->group(function () {
    Route::post('payment/callback/{order}', [PaymentController::class, 'callback']);
    Route::get('payment/status/{order}', [PaymentController::class, 'status']);

});

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::post('orders/{order}/payment/upload-proof', [PaymentController::class, 'uploadProof']);
});

Route::post('webhooks/payment/{gateway}', [PaymentController::class, 'webhook'])
    ->name('payment.webhook');
```

### 3. Admin Panel Integration (Filament)

#### A. Payment Methods Resource

Update `PaymentMethodResource` to include:

-   No create for it just devlober can seed new
-   Gateway selection field (dropdown: cash, bank_transfer, tamara, tabby, amwal)
-   Gateway configuration fields (conditional based on selection)
-   Test mode toggle
-   Credentials fields (encrypted)

#### B. Payment Transactions Resource

Create `app/Filament/Admin/Resources/PaymentTransactionResource.php`:

-   List all transactions
-   Filter by gateway, status, date
-   Filter by "awaiting_review" status
-   View transaction details
-   View payment proof image/PDF
-   Approve/Reject actions for bank transfers
-   Retry failed payments
-   Refund actions
-   Show who reviewed and when

#### C. Order Resource Updates

Update `OrderResource`:

-   Show payment status badge
-   Display transaction details
-   Add "Refund" action button
-   Show payment gateway used
-   Link to payment transaction
-   Add "Review Payment" action for awaiting_review status
-   Display payment proof thumbnail in order details
-   Show review history (reviewer, date, notes)

#### D. Settings Resource (NEW)

Update or create `app/Filament/Admin/Resources/SettingResource.php`:

-   Add section for "Bank Account Details"
-   Fields:
    -   Bank Name (text)
    -   Account Holder Name (text)
    -   Account Number / IBAN (text)
    -   Additional Instructions (textarea)
-   These details will be shown to customers when they select bank transfer

### 4. Order Flow Changes

#### Updated Order Creation Flow:

1. User submits order → `OrderController::store()`
2. Order created with status = 'in_progress', payment_status = 'pending'
3. `PaymentService::initiatePayment()` called
4. For cash: payment_status well review in admin
5. For bank transfer: payment_status = 'awaiting_review', return bank account details
6. For gateways: Create payment session, return payment URL
7. For bank transfer: User uploads payment proof → `PaymentController::uploadProof()`
8. Admin reviews proof → `PaymentService::reviewPaymentProof()`
9. If approved: payment_status = 'paid', order proceeds
10. If rejected: payment_status = 'failed', user can retry
11. User redirected to gateway (for online payments)
12. Gateway redirects back → `PaymentController::callback()`
13. Payment verified → Order status updated
14. Webhook received → `PaymentController::webhook()` (backup verification)

### 5. Database Seeder

Update `PaymentMethodSeeder` to include:

-   Cash payment method (gateway: 'cash')
-   Bank Transfer payment method (gateway: 'bank_transfer', status: 'active')
-   Tamara payment method (gateway: 'tamara', status: 'inactive')
-   Tabby payment method (gateway: 'tabby', status: 'inactive')
-   Amwal payment method (gateway: 'amwal', status: 'inactive')

### 6. Frontend Integration Points

Ensure API returns:

```json
{
    "success": true,
    "message": "Order created successfully",
    "data": {
        "order": {...},
        "payment": {
            "status": "pending",
            "gateway": "tamara",
            "redirect_url": "https://...",
            "transaction_id": "TXN-123"
        }
    }
}
```

**For Bank Transfer:**

```json
{
    "success": true,
    "message": "Order created successfully",
    "data": {
        "order": {...},
        "payment": {
            "status": "awaiting_review",
            "gateway": "bank_transfer",
            "requires_proof_upload": true,
            "bank_account_details": {
                "bank_name": "Saudi National Bank",
                "account_holder": "Your Company Name",
                "account_number": "SA1234567890",
                "iban": "SA1234567890123456789012",
                "swift_code": "NCBKSAJE",
                "instructions": "Please include your order number in the transfer description"
            },
            "upload_url": "/api/v1/orders/123/payment/upload-proof"
        }
    }
}
```

### 7. Error Handling

-   Wrap all payment operations in try-catch
-   Log all payment errors
-   Create payment transaction records for audit
-   Return user-friendly error messages
-   Implement retry logic for transient failures
-   Store payment proofs securely
-   Notify admin when new payment proof uploaded

### 8. Security Considerations

-   Validate webhook signatures
-   Encrypt sensitive gateway credentials
-   Use HTTPS for all payment URLs
-   Implement rate limiting on webhook endpoints
-   Sanitize all payment gateway responses
-   Validate uploaded file MIME types (not just extensions)
-   Store payment proofs outside public directory
-   Only allow authenticated users to upload proofs for their own orders
-   Log all admin review actions for audit trail

## Implementation Guidelines

1. **Follow existing code patterns** in the provided models and services
2. **Use dependency injection** like in `OrderController`
3. **Maintain transaction safety** with DB::beginTransaction()
4. **Follow Laravel conventions** for naming and structure
5. **Add comprehensive PHPDoc comments**
6. **Create interfaces** where appropriate for better abstraction
7. **Use service pattern** consistently
8. **Implement proper error handling** throughout
9. **Add validation** for all payment-related inputs
10. **Write clean, readable code** with meaningful variable names

## Testing Requirements

Create test cases for:

-   Each payment gateway (mock API responses)
-   Payment callback handling
-   Webhook verification
-   Refund operations
-   Order status updates

## Documentation Needed

For each gateway, provide:

1. Setup instructions (credentials, configuration)
2. Testing guide (sandbox mode)
3. Webhook URL configuration
4. Common error codes and solutions
5. Currency and country support

## Additional Notes

-   All payment gateways should support SAR currency
-   System should handle multi-currency in the future
-   Implement proper logging for all payment operations
-   Create admin notifications for failed payments
-   Support partial refunds
-   Track payment attempts per order
-   Language well be for ar and en so use \_\_()

---

## Docs I Will Provide

1. Tamara API documentation
2. Tabby API documentation
3. Amwal API documentation
4. Additional context about the codebase

## Payment Retry & Failure Handling

### Scenario: Payment Connection Fails or Payment Incomplete

The system must handle these scenarios:

1. **User closes browser during payment**
2. **Network connection lost**
3. **Payment gateway timeout**
4. **Payment declined by gateway**
5. **User abandons payment**
6. **Bank transfer proof rejected by admin**
7. **User uploads wrong/invalid payment proof**

### Solution: Payment Retry System

#### A. Order Payment States

```php
// In Order model, payment_status can be:
- 'pending' // Order created, payment not initiated
- 'awaiting_review' // Bank transfer proof uploaded, waiting for admin review
- 'processing' // Payment initiated, waiting for confirmation
- 'paid' // Payment successful
- 'failed' // Payment failed
- 'cancelled' // Payment cancelled by user
- 'expired' // Payment link expired (30 minutes timeout)
```

#### B. Payment Retry Logic

##### Add to Order Model:

```php
// app/Models/Order.php
public function canRetryPayment(): bool
{
    return in_array($this->payment_status, ['failed', 'cancelled', 'expired', 'pending']);
}

public function getLatestPaymentTransaction()
{
    return $this->paymentTransactions()->latest()->first();
}

public function hasSuccessfulPayment(): bool
{
    return $this->payment_status === 'paid';
}
```

##### Add to PaymentTransaction Model:

```php
// app/Models/PaymentTransaction.php
const STATUS_PENDING = 'pending';
const STATUS_PROCESSING = 'processing';
const STATUS_SUCCESS = 'success';
const STATUS_FAILED = 'failed';
const STATUS_EXPIRED = 'expired';
const STATUS_CANCELLED = 'cancelled';

public function scopeLatestForOrder($query, $orderId)
{
    return $query->where('order_id', $orderId)
                 ->latest();
}
```

#### C. Retry Payment Endpoint

Add to `PaymentController`:

```php
/**
 * Retry payment for existing order
 * Allows user to pay again if previous attempt failed
 */
public function retry(Order $order)
{

}

/**
 * Upload payment proof for bank transfer
 */
public function uploadProof(Request $request, Order $order)
{

}

/**
 * Get bank account details for payment
 */
public function bankAccountDetails()
{

}

/**
 * Check order payment status
 * Frontend can poll this to check if payment completed or reviewed
 */
public function checkStatus(Order $order)
{

}
```

#### D. Add Routes for Retry

```php
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::post('orders/{order}/payment/retry', [PaymentController::class, 'retry'])
        ->name('orders.payment.retry');
    Route::get('orders/{order}/payment/status', [PaymentController::class, 'checkStatus'])
        ->name('orders.payment.status');
    Route::post('orders/{order}/payment/upload-proof', [PaymentController::class, 'uploadProof']) // NEW
        ->name('orders.payment.uploadProof');
});

Route::get('payment/bank-account-details', [PaymentController::class, 'bankAccountDetails']) // NEW - No auth needed
    ->name('payment.bankAccountDetails');
```

#### E. Admin Panel - Review

In `OrderResource`, add actions:

```php


Action::make('review_payment')
    ->label('مراجعة الدفع')
    ->icon('heroicon-o-document-check')
    ->color('info')
    ->visible(fn (Order $record) => $record->payment_status === 'awaiting_review')
    ->form([
        ViewField::make('payment_proof')
            ->label('إثبات الدفع')
            ->view('filament.forms.components.payment-proof-viewer')
            ->viewData(fn (Order $record) => [
                'proof_path' => $record->getLatestPaymentTransaction()?->payment_proof_path,
            ]),
        Select::make('decision')
            ->label('القرار')
            ->options([
                'approve' => 'قبول',
                'reject' => 'رفض',
            ])
            ->required(),
        Textarea::make('notes')
            ->label('ملاحظات')
            ->rows(3)
            ->placeholder('سبب الرفض أو ملاحظات إضافية'),
    ])
    ->action(function (Order $record, array $data) {
        try {
            $approve = $data['decision'] === 'approve';
            app(PaymentService::class)->reviewPaymentProof(
                $record,
                $approve,
                $data['notes'] ?? null
            );

            Notification::make()
                ->success()
                ->title($approve ? 'تم قبول الدفع' : 'تم رفض الدفع')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('فشل في مراجعة الدفع')
                ->body($e->getMessage())
                ->send();
        }
    }),

Action::make('view_proof')
    ->label('عرض إثبات الدفع')
    ->icon('heroicon-o-photo')
    ->color('gray')
    ->visible(fn (Order $record) =>
        $record->getLatestPaymentTransaction()?->payment_proof_path
    )
    ->url(fn (Order $record) =>
        route('admin.payment.view-proof', $record->getLatestPaymentTransaction()->id)
    )
    ->openUrlInNewTab(),
```

#### F. Frontend Flow for Failed Payments & Bank Transfer

```
User Journey (Online Payment):
1. User creates order → receives payment URL
2. User clicks payment URL → redirected to gateway
3. [Connection Lost / User Closes Browser]
4. User returns to app → sees "Order Pending Payment"
5. User clicks "Complete Payment" button
6. System calls /orders/{id}/payment/retry
7. New payment session created
8. User redirected to payment gateway again
9. Payment completed → order updated

User Journey (Bank Transfer):
1. User creates order → selects "Bank Transfer"
2. System shows bank account details
3. User transfers money to bank account
4. User uploads payment proof (receipt/screenshot)
5. System marks order as "awaiting_review"
6. Admin receives notification
7. Admin reviews proof in admin panel
8. Admin approves → order status = "paid", process continues
9. Admin rejects → user can upload new proof
```

#### G. Payment Expiration Handling

Add to `PaymentService`:

```php
/**
 * Mark expired payment transactions
 * Run this via scheduled job every 5 minutes
 */
public function markExpiredPayments(): void
{
    // Mark transactions pending/processing for > 30 minutes as expired
    PaymentTransaction::whereIn('status', ['pending', 'processing'])
        ->where('created_at', '<', now()->subMinutes(30))
        ->update(['status' => 'expired']);

    // Update orders with expired payments
    Order::where('payment_status', 'processing')
        ->whereDoesntHave('paymentTransactions', function ($query) {
            $query->where('status', 'success');
        })
        ->where('updated_at', '<', now()->subMinutes(30))
        ->update(['payment_status' => 'expired']);
}
```

Add to `app/Console/Kernel.php`:

```php
$schedule->call(function () {
    app(PaymentService::class)->markExpiredPayments();
})->everyFiveMinutes();
```

---

## Implementation Phases

### 📋 PHASE 1: Database Foundation

```
PHASE 1: Database & Configuration Setup

Create the following files:

1. Migration: create_payment_transactions_table
   - Columns: id, order_id, payment_method_id, gateway, transaction_id, amount, currency, status, request_payload, response_payload, error_message, expires_at, timestamps

2. Migration: add_payment_fields_to_orders_table
   - Add columns: payment_status (enum), payment_transaction_id

3. Migration: add_gateway_to_payment_methods_table
   - Add column: gateway (string), gateway_config (json)

4. Configuration file: config/payment-gateways.php
   - Include all gateway configurations (Tamara, Tabby, Amwal, Cash)

5. Update .env.example and .env with all payment gateway variables

Run migrations and verify database structure is correct before proceeding to Phase 2.
```

**Deliverables:**

-   ✅ 3 migration files
-   ✅ config/payment-gateways.php
-   ✅ Updated .env.example

---

### 📋 PHASE 2: Models & Relationships

```
PHASE 2: Create Models & Relationships

Using the existing code patterns in User.php and Order.php:

1. Create app/Models/PaymentTransaction.php
   - Define fillable fields
   - Add status constants
   - Create relationships to Order and PaymentMethod
   - Add scopes: scopeSuccessful, scopeFailed, scopePending
   - Add methods: isSuccessful(), isFailed(), isPending(), canRetry()

2. Update app/Models/Order.php
   - Add payment_status to fillable
   - Add relationship: paymentTransactions()
   - Add methods: canRetryPayment(), hasSuccessfulPayment(), getLatestPaymentTransaction()
   - Add payment status constants

3. Update app/Models/PaymentMethod.php
   - Add gateway field to fillable
   - Add method: isGateway(string $gateway), requiresRedirect(), isCash()

Test relationships work correctly before proceeding to Phase 3.
```

**Deliverables:**

-   ✅ PaymentTransaction model
-   ✅ Updated Order model
-   ✅ Updated PaymentMethod model

---

### 📋 PHASE 3: Abstract Gateway & Factory

```
PHASE 3: Payment Gateway Architecture

Create the core payment gateway infrastructure:

1. Create app/Services/PaymentGateways/AbstractPaymentGateway.php
   - Abstract class with required methods:
     * createPayment(Order $order): array
     * capturePayment(string $transactionId): array
     * refundPayment(string $transactionId, float $amount): array
     * getPaymentStatus(string $transactionId): array
     * handleWebhook(array $payload): array
     * validateWebhookSignature(Request $request): bool
   - Protected helper methods for HTTP requests

2. Create app/Services/PaymentGateways/PaymentGatewayFactory.php
   - Static method: make(string $gateway): AbstractPaymentGateway
   - Static method: makeFromPaymentMethod(PaymentMethod $method): AbstractPaymentGateway
   - Throw exception for unsupported gateways

3. Create app/Services/PaymentGateways/CashGateway.php
   - Extend AbstractPaymentGateway
   - Implement all required methods (cash = instant success)
   - No webhook needed

4. Create app/Services/PaymentGateways/BankTransferGateway.php
   - Extend AbstractPaymentGateway
   - Implement all required methods
   - requiresProofUpload() returns true
   - requiresAdminReview() returns true
   - createPayment() returns bank account details
   - No redirect URL needed

Do NOT implement Tamara/Tabby/Amwal yet - just the structure.
Test that CashGateway and BankTransferGateway work before proceeding to Phase 4.
```

**Deliverables:**

-   ✅ AbstractPaymentGateway class
-   ✅ PaymentGatewayFactory class
-   ✅ CashGateway implementation
-   ✅ BankTransferGateway implementation

---

### 📋 PHASE 4: Payment Service

```
PHASE 4: Core Payment Service

Create app/Services/PaymentService.php that orchestrates all payment operations:

Required Methods:
1. initiatePayment(Order $order): array
   - Get payment method for order
   - Create PaymentTransaction record (status: pending)
   - Call appropriate gateway via factory
   - Update transaction with response
   - Return payment data (url, transaction_id, etc.)

2. processPaymentCallback(Order $order, string $transactionId, array $data): bool
   - Verify transaction belongs to order
   - Get gateway and verify payment status
   - Update PaymentTransaction status
   - Update Order payment_status and status
   - Return success/failure

3. handleWebhook(string $gateway, array $payload): array
   - Get gateway instance
   - Validate webhook signature
   - Process webhook data
   - Update order and transaction
   - Return response for gateway

4. refundOrder(Order $order, ?float $amount = null): array
   - Verify order is paid
   - Call gateway refund method
   - Create refund transaction record
   - Update order status
   - Return refund data

5. checkPaymentStatus(Order $order): array
   - Get latest transaction
   - Query gateway for current status
   - Update local status if different
   - Return status data

6. markExpiredPayments(): void
   - Find pending/processing payments > 30 minutes old
   - Mark as expired
   - Run via scheduled task

7. uploadPaymentProof(Order $order, UploadedFile $file): string
   - Validate file type and size
   - Store file securely (storage/app/payment-proofs/)
   - Create PaymentTransaction record with proof path
   - Update order payment_status to 'awaiting_review'
   - Notify admin (optional)
   - Return file path

8. reviewPaymentProof(Order $order, bool $approve, ?string $notes = null): bool
   - Get latest transaction
   - Verify transaction has payment proof
   - If approve:
     * Update transaction status to 'success'
     * Update order payment_status to 'paid'
     * Update order status to process
   - If reject:
     * Update transaction status to 'failed'
     * Update order payment_status to 'failed'
     * Store review notes
   - Store reviewer info (admin_id, reviewed_at)
   - Return success/failure

9. getBankAccountDetails(): array
   - Get bank account details from settings
   - Return formatted array
   - If not configured, throw exception

Use dependency injection and follow existing service patterns.
Add comprehensive error handling and logging.
Test with CashGateway and BankTransferGateway before proceeding to Phase 5.
```

**Deliverables:**

-   ✅ PaymentService class
-   ✅ All required methods implemented
-   ✅ Error handling and logging

---

### 📋 PHASE 5: Update OrderController

````
PHASE 5: Integrate Payment into Order Creation

Update app/Http/Controllers/Api/V1/OrderController.php:

1. Inject PaymentService into constructor

2. Modify store() method:
   - After creating order (existing code)
   - Call $this->paymentService->initiatePayment($order)
   - Handle payment response
   - Return order + payment data to frontend

3. Update response structure:
   ```json
   {
       "success": true,
       "message": "Order created successfully",
       "data": {
           "order": {...},
           "payment": {
               "status": "pending",
               "gateway": "tamara",
               "redirect_url": "https://...",
               "transaction_id": "TXN-123",
               "expires_at": "2024-01-01 12:30:00"
           }
       }
   }
````

For Bank Transfer:

```json
{
    "success": true,
    "message": "Order created successfully",
    "data": {
        "order": {...},
        "payment": {
            "status": "awaiting_review",
            "gateway": "bank_transfer",
            "requires_proof_upload": true,
            "bank_account_details": {...},
            "upload_url": "/api/v1/orders/123/payment/upload-proof"
        }
    }
}
```

4. Add error handling for payment failures:
    - If payment initiation fails, keep order but mark as 'pending payment'
    - Return user-friendly error message
    - Allow retry later

Keep all existing order creation logic intact.
Test thoroughly with CashGateway and BankTransferGateway before proceeding to Phase 6.

```

**Deliverables:**
- ✅ Updated OrderController::store()
- ✅ Payment integration in order flow
- ✅ Bank transfer flow with account details
- ✅ Proper error handling

---

### 📋 PHASE 6: Payment Controller

```

PHASE 6: Create Payment Controller for Callbacks & Status

Create app/Http/Controllers/Api/V1/PaymentController.php:

Required Methods:

1. callback(Request $request, Order $order)

    - Verify order exists and belongs to user (if authenticated)
    - Get transaction_id from request
    - Call PaymentService::processPaymentCallback()
    - Return success/failure response
    - Redirect user to appropriate page (success/failure)

2. webhook(Request $request, string $gateway)

    - Call PaymentService::handleWebhook()
    - Return 200 OK for gateway
    - Log all webhook data

3. retry(Order $order)

    - Verify user owns order
    - Check order->canRetryPayment()
    - Check order not expired (< 24 hours old)
    - Cancel previous pending transactions
    - Call PaymentService::initiatePayment()
    - Return new payment data

4. checkStatus(Order $order)

    - Verify user owns order
    - Return current payment status
    - Return latest transaction details
    - Return can_retry flag
    - Return requires_proof_upload flag
    - Return awaiting_review flag
    - Return payment_proof details

5. uploadProof(Request $request, Order $order)

    - Verify user owns order
    - Verify order is bank transfer
    - Validate file (jpg, jpeg, png, pdf, max 5MB)
    - Call PaymentService::uploadPaymentProof()
    - Return success with updated order

6. bankAccountDetails()
    - Call PaymentService::getBankAccountDetails()
    - Return bank account info
    - No authentication required (public info)

Add routes to routes/api.php:

-   POST /v1/payment/callback/{order}
-   POST /webhooks/payment/{gateway} (no auth)
-   POST /v1/orders/{order}/payment/retry (auth)
-   GET /v1/orders/{order}/payment/status (auth)
-   POST /v1/orders/{order}/payment/upload-proof (auth)
-   GET /api/v1/payment/bank-account-details (no auth)

Follow existing controller patterns in OrderController.
Add comprehensive validation and error handling.

```

**Deliverables:**
- ✅ PaymentController class
- ✅ All endpoints implemented (including bank transfer endpoints)
- ✅ Routes configured
- ✅ File upload validation
- ✅ Validation and error handling

---

### 📋 PHASE 7: Filament Admin Integration

```

PHASE 7: Admin Panel Resources

Create Filament resources for payment management:

1. Create app/Filament/Admin/Resources/PaymentTransactionResource.php

    - List all transactions with filters (gateway, status, date)
    - Add filter for 'awaiting_review' status
    - View transaction details (request/response payload)
    - Show payment proof image/PDF viewer
    - Add "Approve Payment" action (bulk & single)
    - Add "Reject Payment" action with notes field
    - Add "Refund" action (if status = success)
    - Add "Check Status" action
    - Show related order link
    - Show who reviewed and when
    - Display formatted JSON payloads

2. Update app/Filament/Admin/Resources/Orders/OrderResource.php

    - Add payment_status badge in table
    - Show payment gateway in infolist
    - Add "Retry Payment" action (if canRetryPayment)
    - Add "Review Payment" action (if awaiting_review)
    - Add "View Payment Proof" action
    - Add "Refund Payment" action (if paid)
    - Show payment transactions in relation manager
    - Add payment status filter
    - Add 'awaiting_review' filter
    - Display payment proof thumbnail in order details
    - Show review history (reviewer, date, notes)

3. Update app/Filament/Admin/Resources/PaymentMethodResource.php

    - Add gateway selection field (Select: cash, bank_transfer, tamara, tabby, amwal)
    - Add gateway configuration fields (JSON/KeyValue)
    - For bank_transfer: show bank account fields
    - Show which gateway is used
    - Add test credentials fields
    - Add "Test Connection" action

4. Update/Create app/Filament/Admin/Resources/SettingResource.php

    - Add "Bank Account Details" section
    - Fields: bank_name, account_holder, account_number, iban, swift_code, branch, instructions
    - Store in settings table as JSON
    - These details shown to customers

5. Create app/Http/Controllers/Admin/PaymentProofController.php
    - viewProof(PaymentTransaction $transaction) method
    - Download proof method
    - Protect with admin auth
    - Add route: /admin/payment/proof/{transaction}

Follow existing Filament patterns in OrderResource.
Use Arabic labels for all fields.
Add proper authorization checks.

```

**Deliverables:**
- ✅ PaymentTransactionResource with review actions
- ✅ Updated OrderResource with bank transfer support
- ✅ Updated PaymentMethodResource with bank account fields
- ✅ Settings page for bank account details
- ✅ PaymentProofController for viewing proofs

---

### 📋 PHASE 8: Tamara Gateway
PHASE 8: Implement Tamara Payment Gateway

I will provide Tamara API documentation.

Create app/Services/PaymentGateways/TamaraGateway.php:

1. Extend AbstractPaymentGateway
2. Implement all required methods using Tamara API
3. Handle Tamara-specific requirements:
   - Checkout session creation
   - Order capture/authorization
   - Refund processing
   - Webhook signature validation
   - Status checking

4. Map Tamara statuses to our internal statuses
5. Handle Tamara error codes
6. Implement proper logging
7. Use configuration from config/payment-gateways.php

Requirements:
- Support SAR currency
- Handle customer data properly
- Validate order amounts
- Store all API requests/responses
- Implement timeout handling
- Add retry logic for transient failures

Test thoroughly with Tamara sandbox before proceeding to Phase 9.
```

**Deliverables:**

-   ✅ TamaraGateway implementation
-   ✅ Error handling
-   ✅ Webhook handling
-   ✅ Documentation

---

### 📋 PHASE 9: Tabby Gateway

```
PHASE 9: Implement Tabby Payment Gateway

I will provide Tabby API documentation.

Create app/Services/PaymentGateways/TabbyGateway.php:

Follow same pattern as TamaraGateway (Phase 8).
Implement all required methods using Tabby API.
Handle Tabby-specific requirements and flows.

Test with Tabby sandbox credentials.
```

**Deliverables:**

-   ✅ TabbyGateway implementation
-   ✅ Complete integration

---

### 📋 PHASE 10: Amwal Gateway

```
PHASE 10: Implement Amwal Payment Gateway

I will provide Amwal API documentation.

Create app/Services/PaymentGateways/AmwalGateway.php:

Follow same pattern as TamaraGateway and TabbyGateway.
Implement all required methods using Amwal API.

Test with Amwal sandbox credentials.
```

**Deliverables:**

-   ✅ AmwalGateway implementation
-   ✅ Complete integration

---

### 📋 PHASE 11: Seeder & Final Testing

```
PHASE 11: Database Seeder & Complete Testing

1. Create database/seeders/PaymentMethodSeeder.php:
   - Cash payment method (active, gateway: 'cash')
   - Tamara (inactive, gateway: 'tamara')
   - Tabby (inactive, gateway: 'tabby')
   - Amwal (inactive, gateway: 'amwal')

2. Create comprehensive test suite:
   - Test order creation with each gateway
   - Test payment retry flow
   - Test webhook handling
   - Test status checking
   - Test refunds
   - Test expired payments cleanup

3. Create setup documentation (README-PAYMENT.md):
   - Installation steps
   - Configuration guide for each gateway
   - Testing instructions
   - Troubleshooting guide
   - API endpoint documentation

4. Add scheduled task to app/Console/Kernel.php:
   - Mark expired payments every 5 minutes

5. Final integration testing:
   - Test complete order flow with each gateway
   - Test retry scenarios
   - Test admin panel actions
   - Verify all error cases handled
```

**Deliverables:**

-   ✅ PaymentMethodSeeder
-   ✅ Complete documentation
-   ✅ Scheduled tasks configured
-   ✅ All tests passing

---

### 📋 PHASE 12: Postman Collection (Do This After Phase 11)

Create this prompt for Cursor:

````
PHASE 12: Create Postman Collection for Payment APIs

I already have a Postman collection for orders. Extend it or create a new collection for payment endpoints.

Create a comprehensive Postman collection with the following:

## Collection Structure

### Folder: Payment Flow
1. **Create Order with Payment** (POST)
   - Endpoint: {{base_url}}/api/v1/orders
   - Headers: Authorization: Bearer {{token}} (I use api dog inhert)
   - Body: Complete order creation with payment_method_id
   - Tests:
     * Save order_id to environment
     * Save payment.redirect_url to environment
     * Verify payment object exists
     * Check payment_status is 'pending' or 'paid'

2. **Check Payment Status** (GET)
   - Endpoint: {{base_url}}/api/v1/orders/{{order_id}}/payment/status
   - Headers: Authorization: Bearer {{token}}
   - Tests:
     * Verify payment_status returned
     * Check can_retry flag
     * Verify transaction details

3. **Retry Payment** (POST)
   - Endpoint: {{base_url}}/api/v1/orders/{{order_id}}/payment/retry
   - Headers: Authorization: Bearer {{token}}
   - Tests:
     * Verify new payment URL generated
     * Check transaction_id is different
     * Verify expires_at is updated

### Folder: Payment Callbacks
4. **Payment Success Callback** (POST)
   - Endpoint: {{base_url}}/api/v1/payment/callback/{{order_id}}
   - Query Params:
     * transaction_id={{transaction_id}}
     * status=success
     * payment_id={{payment_id}}
   - Tests:
     * Verify order status updated
     * Check payment_status is 'paid'

5. **Payment Failed Callback** (POST)
   - Endpoint: {{base_url}}/api/v1/payment/callback/{{order_id}}
   - Query Params:
     * transaction_id={{transaction_id}}
     * status=failed
     * error_message=Payment declined
   - Tests:
     * Verify payment_status is 'failed'
     * Check can_retry is true

6. **Payment Cancelled Callback** (POST)
   - Endpoint: {{base_url}}/api/v1/payment/callback/{{order_id}}
   - Query Params:
     * transaction_id={{transaction_id}}
     * status=cancelled
   - Tests:
     * Verify payment_status is 'cancelled'
     * Check can_retry is true

### Folder: Webhooks (For Testing)
7. **Tamara Webhook** (POST)
   - Endpoint: {{base_url}}/webhooks/payment/tamara
   - Headers: X-Tamara-Signature: {{signature}}
   - Body: Tamara webhook payload (JSON)
   - Tests: Verify 200 OK response

8. **Tabby Webhook** (POST)
   - Endpoint: {{base_url}}/webhooks/payment/tabby
   - Headers: X-Tabby-Signature: {{signature}}
   - Body: Tabby webhook payload (JSON)
   - Tests: Verify 200 OK response

9. **Amwal Webhook** (POST)
   - Endpoint: {{base_url}}/webhooks/payment/amwal
   - Headers: X-Amwal-Signature: {{signature}}
   - Body: Amwal webhook payload (JSON)
   - Tests: Verify 200 OK response

### Folder: Payment Methods
10. **Get Active Payment Methods** (GET)
    - Endpoint: {{base_url}}/api/v1/payment-methods
    - Tests:
      * Verify methods returned
      * Check each has gateway field
      * Verify active methods only

### Folder: Order History with Payments
11. **Get Order with Payment Details** (GET)
    - Endpoint: {{base_url}}/api/v1/orders/{{order_id}}
    - Headers: Authorization: Bearer {{token}}
    - Tests:
      * Verify payment details included
      * Check payment_status exists
      * Verify payment_method included
      * Check transaction history

12. **Get User Orders** (GET)
    - Endpoint: {{base_url}}/api/v1/orders
    - Headers: Authorization: Bearer {{token}}
    - Query Params:
      * payment_status=paid
      * payment_status=pending
    - Tests:
      * Verify filtering by payment_status works
      * Check pagination

### Folder: Error Scenarios
13. **Retry Paid Order (Should Fail)** (POST)
    - Endpoint: {{base_url}}/api/v1/orders/{{paid_order_id}}/payment/retry
    - Headers: Authorization: Bearer {{token}}
    - Tests:
      * Verify error message
      * Check status code is 400

14. **Retry Expired Order (Should Fail)** (POST)
    - Endpoint: {{base_url}}/api/v1/orders/{{old_order_id}}/payment/retry
    - Headers: Authorization: Bearer {{token}}
    - Tests:
      * Verify "order expired" error
      * Check status code is 400

15. **Access Another User's Order Payment** (GET)
    - Endpoint: {{base_url}}/api/v1/orders/{{other_user_order_id}}/payment/status
    - Headers: Authorization: Bearer {{wrong_token}}
    - Tests:
      * Verify unauthorized error
      * Check status code is 403

### Folder: Bank Transfer Flow
16. **Get Bank Account Details** (GET)
    - Endpoint: {{base_url}}/api/v1/payment/bank-account-details
    - No auth required
    - Tests:
      * Verify bank details returned
      * Check all fields present

17. **Create Order with Bank Transfer** (POST)
    - Endpoint: {{base_url}}/api/v1/orders
    - Headers: Authorization: Bearer {{token}}
    - Body: Order with bank_transfer payment_method_id
    - Tests:
      * Verify payment_status is 'awaiting_review'
      * Check bank_account_details in response
      * Verify requires_proof_upload is true
      * Save order_id and upload_url

18. **Upload Payment Proof** (POST)
    - Endpoint: {{base_url}}/api/v1/orders/{{order_id}}/payment/upload-proof
    - Headers:
      * Authorization: Bearer {{token}}
      * Content-Type: multipart/form-data
    - Body: Form-data with payment_proof file
    - Tests:
      * Verify proof uploaded successfully
      * Check payment_status is 'awaiting_review'
      * Verify awaiting_review flag

19. **Check Payment Status (Awaiting Review)** (GET)
    - Endpoint: {{base_url}}/api/v1/orders/{{order_id}}/payment/status
    - Headers: Authorization: Bearer {{token}}
    - Tests:
      * Verify awaiting_review is true
      * Check has_payment_proof is true
      * Verify reviewed_at is null

### Folder: Cash Payment Flow
16. **Create Order with Cash** (POST)
    - Endpoint: {{base_url}}/api/v1/orders
    - Headers: Authorization: Bearer {{token}}
    - Body: Order with cash payment_method_id
    - Tests:
      * Verify payment_status is 'paid' immediately
      * Check no redirect_url
      * Verify order completed

### Folder: Admin Actions (Optional - for testing admin endpoints)
17. **Approve Payment Proof** (POST)
    - Endpoint: {{base_url}}/admin/api/orders/{{order_id}}/approve-payment
    - Headers: Authorization: Bearer {{admin_token}}
    - Body: { "notes": "Payment verified" }
    - Tests:
      * Verify order payment_status updated to 'paid'
      * Check reviewed_at is set

18. **Reject Payment Proof** (POST)
    - Endpoint: {{base_url}}/admin/api/orders/{{order_id}}/reject-payment
    - Headers: Authorization: Bearer {{admin_token}}
    - Body: { "notes": "Invalid receipt" }
    - Tests:
      * Verify payment_status is 'failed'
      * Check user can upload new proof

19. **View Payment Proof** (GET)
    - Endpoint: {{base_url}}/admin/payment/proof/{{transaction_id}}
    - Headers: Authorization: Bearer {{admin_token}}
    - Tests:
      * Verify image/PDF returned
      * Check content-type

## Environment Variables
Add these to your Postman environment:

```json
{
  "base_url": "http://localhost:8000",
  "token": "",
  "admin_token": "",
  "order_id": "",
  "transaction_id": "",
  "payment_id": "",
  "paid_order_id": "",
  "old_order_id": "",
  "other_user_order_id": "",
  "cash_payment_method_id": "",
  "bank_transfer_payment_method_id": "",
  "tamara_payment_method_id": "",
  "tabby_payment_method_id": "",
  "amwal_payment_method_id": ""
}
````

## Pre-request Scripts

Add to collection level:

## Test Scripts (Collection Level)

Add to collection level:

## Export Format

Export as Postman Collection v2.1 JSON format.
Name: "Payment Gateway Integration API"
Description: "Complete API collection for testing payment gateway integrations including Tamara, Tabby, Amwal, and Cash payments."

## Documentation

Add descriptions to each request explaining:

-   Purpose of the endpoint
-   Required authentication
-   Expected response
-   Common error scenarios
-   Example use cases

```

**Deliverables:**
- ✅ Complete Postman collection JSON file
- ✅ All endpoints documented (including bank transfer)
- ✅ Environment variables configured
- ✅ Test scripts for all requests
- ✅ Pre-request scripts
- ✅ Test runner sequence with bank transfer flow
- ✅ Export-ready collection

---

## Summary: Implementation Checklist

- [ ] Phase 1: Database & Config (1-2 hours)
- [ ] Phase 2: Models (1-2 hours)
- [ ] Phase 3: Gateway Architecture (2-3 hours)
- [ ] Phase 4: Payment Service (2-3 hours)
- [ ] Phase 5: Update OrderController (1-2 hours)
- [ ] Phase 6: Payment Controller (2-3 hours)
- [ ] Phase 7: Filament Admin (3-4 hours) ← Extended for bank transfer features
- [ ] Phase 8: Tamara Gateway (3-4 hours)
- [ ] Phase 9: Tabby Gateway (3-4 hours)
- [ ] Phase 10: Amwal Gateway (3-4 hours)
- [ ] Phase 11: Seeder & Testing (2-3 hours)
- [ ] Phase 12: Postman Collection (1-2 hours) ← Extended for bank transfer endpoints

**Total Estimated Time: 24-35 hours**

---

## Expected Deliverables (Complete System)
1. ✅ All migration files (3 files + bank_account_details in settings)
2. ✅ PaymentTransaction model with proof upload fields
3. ✅ Updated Order & PaymentMethod models
4. ✅ Abstract payment gateway class with proof upload methods
5. ✅ Gateway factory
6. ✅ 5 Gateway implementations (Cash, Bank Transfer, Tamara, Tabby, Amwal)
7. ✅ PaymentService with proof upload & review methods
8. ✅ Updated OrderController with bank transfer support
9. ✅ PaymentController with upload & review endpoints
10. ✅ All routes configured (including bank transfer routes)
11. ✅ 4 Filament resources (Transactions, Orders, PaymentMethods, Settings)
12. ✅ PaymentProofController for viewing proofs
13. ✅ Configuration file with bank transfer config
14. ✅ Seeder file with bank transfer method
15. ✅ Scheduled tasks
16. ✅ Complete documentation with bank transfer flow
17. ✅ Test suite including bank transfer scenarios
18. ✅ Postman collection with bank transfer endpoints
19. ✅ File upload validation and storage system
20. ✅ Admin review system for bank transfers

Please implement this system phase by phase, ensuring each phase is complete and tested before moving to the next.
```
