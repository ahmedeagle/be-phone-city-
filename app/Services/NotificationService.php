<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\Review;
use App\Models\ContactRequest;
use App\Notifications\OrderNotification;
use App\Notifications\TicketNotification;
use App\Notifications\ReviewNotification;
use App\Notifications\ContactRequestNotification;
use App\Notifications\PaymentProofNotification;
use App\Models\PaymentTransaction;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

use function Laravel\Prompts\info;

class NotificationService
{
    /**
     * Force Arabic locale for all notifications (mail + database).
     */
    private function forceArabicLocale(object $notification): object
    {
        // Laravel notifications support ->locale('ar'); keep it defensive for non-standard objects.
        if (method_exists($notification, 'locale')) {
            $notification->locale('ar');
        }

        return $notification;
    }

    /**
     * Notify about a new order
     */
    public function notifyOrderCreated(Order $order)
    {
        // Notify User
        if ($order->user) {
            $order->user->notify($this->forceArabicLocale(new OrderNotification($order, 'created')));
        }

        // Skip admin notification for bank_transfer — admins were already notified when proof was uploaded
        if ($order->paymentMethod?->gateway === 'bank_transfer') {
            return;
        }

        // Notify Admins with 'orders.show' permission
        $this->notifyAdmins(
            permission: 'orders.show',
            notification: $this->forceArabicLocale(new OrderNotification($order, 'created')),
            filamentTitle: __('New Order Received'),
            filamentBody: __('Order #') . $order->order_number . ' ' . __('has been placed.'),
            filamentIcon: 'heroicon-o-shopping-cart',
            filamentColor: 'success',
            actionUrl: route('filament.admin.resources.orders.view', ['record' => $order->id])
        );
    }

    /**
     * Notify about order status change.
     * Only notifies the customer (user) — admins are not notified for status updates.
     */
    public function notifyOrderStatusChanged(Order $order)
    {
        if ($order->user) {
            $order->user->notify($this->forceArabicLocale(new OrderNotification($order, 'status_updated')));
        }
    }

    /**
     * Notify about a new ticket
     */
    public function notifyTicketCreated(Ticket $ticket)
    {
        // Notify User if authenticated
        if ($ticket->user) {
            info($ticket->user);
            $ticket->user->notify($this->forceArabicLocale(new TicketNotification($ticket, 'created')));
        }

        // Notify Admins with 'tickets.show' permission
        $this->notifyAdmins(
            permission: 'tickets.show',
            notification: $this->forceArabicLocale(new TicketNotification($ticket, 'created')),
            filamentTitle: __('New Support Ticket'),
            filamentBody: __('Ticket #') . $ticket->ticket_number . ': ' . $ticket->subject,
            filamentIcon: 'heroicon-o-ticket',
            filamentColor: 'info',
            actionUrl: route('filament.admin.resources.tickets.view', ['record' => $ticket->id])
        );
    }

    /**
     * Notify about ticket update/reply
     */
    public function notifyTicketUpdated(Ticket $ticket)
    {
        if ($ticket->user) {
            $ticket->user->notify($this->forceArabicLocale(new TicketNotification($ticket, 'updated')));
        }
    }

    /**
     * Notify about a new review
     */
    public function notifyReviewCreated(Review $review)
    {
        // Notify Admins with 'comments.show' permission (assuming reviews are under comments or similar)
        // Adjust permission as per PermissionSeeder
        $this->notifyAdmins(
            permission: 'comments.show',
            notification: $this->forceArabicLocale(new ReviewNotification($review)),
            filamentTitle: __('New Product Review'),
            filamentBody: __('New review for') . ' ' . $review->product->name,
            filamentIcon: 'heroicon-o-star',
            filamentColor: 'warning'
            // Add actionUrl if review resource exists in Filament
        );
    }

    /**
     * Notify about a new contact request
     */
    public function notifyContactRequestCreated(ContactRequest $contactRequest)
    {
        // Notify Admins with 'contact_requests.show' permission
        $this->notifyAdmins(
            permission: 'contact_requests.show',
            notification: $this->forceArabicLocale(new ContactRequestNotification($contactRequest)),
            filamentTitle: __('New Contact Request'),
            filamentBody: __('From') . ': ' . $contactRequest->name,
            filamentIcon: 'heroicon-o-envelope',
            filamentColor: 'primary'
            // Add actionUrl if contact request resource exists in Filament
        );
    }

    /**
     * Notify about payment proof upload
     */
    public function notifyPaymentProofUploaded(PaymentTransaction $transaction)
    {
        $order = $transaction->order;

        // Notify User
        if ($order->user) {
            $order->user->notify($this->forceArabicLocale(new PaymentProofNotification($transaction, 'uploaded')));
        }

        // Notify Admins with 'payment_transactions.show' permission
        $this->notifyAdmins(
            permission: 'payment_transactions.show',
            notification: $this->forceArabicLocale(new PaymentProofNotification($transaction, 'uploaded_admin')),
            filamentTitle: __('New Payment Proof Requires Review'),
            filamentBody: __('Payment proof uploaded for order #') . $order->order_number . ' ' . __('requires review.'),
            filamentIcon: 'heroicon-o-document-check',
            filamentColor: 'warning',
            actionUrl: route('filament.admin.resources.payment-transactions.view', ['record' => $transaction->id])
        );
    }

    /**
     * Notify user about payment proof review result (approve/reject)
     */
    public function notifyPaymentProofReviewed(PaymentTransaction $transaction, bool $approved, ?string $notes = null)
    {
        $order = $transaction->order;

        // Only notify user
        if ($order->user) {
            try {
                $type = $approved ? 'approved' : 'rejected';
                $order->user->notify($this->forceArabicLocale(new PaymentProofNotification($transaction, $type, $notes)));
            } catch (\Exception $e) {
                // Log error but don't throw - database notification should still be saved
                Log::warning('Failed to send payment proof review notification to user', [
                    'user_id' => $order->user->id,
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
                // Re-throw only if it's not a mail-related exception
                if (!str_contains($e->getMessage(), 'mail') && !str_contains($e->getMessage(), 'smtp')) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Internal helper to notify admins based on permission
     */
    protected function notifyAdmins(
        string $permission,
        $notification,
        string $filamentTitle,
        string $filamentBody,
        string $filamentIcon = 'heroicon-o-bell',
        string $filamentColor = 'primary',
        string $actionUrl = null
    ) {
        $admins = Admin::permission($permission)->get();

        if ($admins->isEmpty()) {
            return;
        }

        // Send Laravel Database/Mail notification
        Notification::send($admins, $this->forceArabicLocale($notification));

        // Send Filament Notification
        foreach ($admins as $admin) {
            $fNotification = FilamentNotification::make()
                ->title($filamentTitle)
                ->body($filamentBody)
                ->icon($filamentIcon)
                ->color($filamentColor);

            if ($actionUrl) {
                $fNotification->actions([
                    Action::make('view')
                        ->label(__('View'))
                        ->url($actionUrl),
                ]);
            }

            $fNotification->sendToDatabase($admin);
        }
    }
}
