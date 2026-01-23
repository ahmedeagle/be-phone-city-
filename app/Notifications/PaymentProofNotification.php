<?php

namespace App\Notifications;

use App\Models\PaymentTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentProofNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $transaction;
    protected $type; // 'uploaded' for user, 'uploaded_admin' for admin, 'approved', 'rejected'
    protected $notes;

    public function __construct(PaymentTransaction $transaction, string $type = 'uploaded', ?string $notes = null)
    {
        $this->transaction = $transaction;
        $this->type = $type;
        $this->notes = $notes;
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $order = $this->transaction->order;
        $url = config('app.url') . '/orders/' . $order->id;

        $subject = match($this->type) {
            'uploaded' => __('Payment Proof Uploaded'),
            'uploaded_admin' => __('New Payment Proof Requires Review'),
            'approved' => __('Payment Proof Approved'),
            'rejected' => __('Payment Proof Rejected'),
            default => __('Payment Proof Update'),
        };

        $message = (new MailMessage)
            ->subject($subject . ' - ' . __('Order #') . $order->order_number)
            ->greeting(__('Hello') . ' ' . $notifiable->name);

        if ($this->type === 'uploaded') {
            // Notification for user
            $message->line(__('Your payment proof has been uploaded successfully.'))
                ->line(__('Order Number') . ': ' . $order->order_number)
                ->line(__('Amount') . ': ' . $this->transaction->amount . ' ' . $this->transaction->currency)
                ->line(__('Your payment is now under review. You will be notified once it is approved.'));
        } elseif ($this->type === 'uploaded_admin') {
            // Notification for admin
            $message->line(__('A new payment proof has been uploaded and requires review.'))
                ->line(__('Order Number') . ': ' . $order->order_number)
                ->line(__('Amount') . ': ' . $this->transaction->amount . ' ' . $this->transaction->currency)
                ->line(__('Transaction ID') . ': ' . ($this->transaction->transaction_id ?? __('N/A')));
        } elseif ($this->type === 'approved') {
            // Notification for user - payment approved
            $message->line(__('Your payment proof has been approved!'))
                ->line(__('Order Number') . ': ' . $order->order_number)
                ->line(__('Amount') . ': ' . $this->transaction->amount . ' ' . $this->transaction->currency)
                ->line(__('Your order is now being processed.'));
            if ($this->notes) {
                $message->line(__('Notes') . ': ' . $this->notes);
            }
        } elseif ($this->type === 'rejected') {
            // Notification for user - payment rejected
            $message->line(__('Your payment proof has been rejected.'))
                ->line(__('Order Number') . ': ' . $order->order_number)
                ->line(__('Amount') . ': ' . $this->transaction->amount . ' ' . $this->transaction->currency)
                ->line(__('You can upload a new payment proof.'));
            if ($this->notes) {
                $message->line(__('Reason') . ': ' . $this->notes);
            }
        }

        $actionLabel = match($this->type) {
            'uploaded' => __('View Order'),
            'uploaded_admin' => __('Review Payment'),
            'approved' => __('View Order'),
            'rejected' => __('Upload New Proof'),
            default => __('View Order'),
        };

        return $message->action($actionLabel, $url)
            ->line(__('Thank you!'));
    }

    public function toDatabase($notifiable): array
    {
        $order = $this->transaction->order;

        if ($this->type === 'uploaded') {
            // Notification for user
            $title = __('Payment Proof Uploaded');
            $message = __('Your payment proof for order #') . $order->order_number . ' ' . __('has been uploaded successfully and is under review.');
        } elseif ($this->type === 'uploaded_admin') {
            // Notification for admin
            $title = __('New Payment Proof Requires Review');
            $message = __('Payment proof uploaded for order #') . $order->order_number . ' ' . __('requires review.');
        } elseif ($this->type === 'approved') {
            // Notification for user - payment approved
            $title = __('Payment Proof Approved');
            $message = __('Your payment proof for order #') . $order->order_number . ' ' . __('has been approved. Your order is now being processed.');
        } else { // rejected
            // Notification for user - payment rejected
            $title = __('Payment Proof Rejected');
            $message = __('Your payment proof for order #') . $order->order_number . ' ' . __('has been rejected. You can upload a new payment proof.');
        }

        return [
            'transaction_id' => $this->transaction->id,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'type' => $this->type,
            'title' => $title,
            'message' => $message,
            'amount' => $this->transaction->amount,
            'currency' => $this->transaction->currency,
            'notes' => $this->notes,
        ];
    }
}
