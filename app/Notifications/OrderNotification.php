<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class OrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;
    protected $type;

    public function __construct(Order $order, string $type = 'created')
    {
        $this->order = $order;
        $this->type = $type;
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $statusLabel = $this->order->getStatusDisplayName();
        $url = config('app.url') . '/orders/' . $this->order->id;

        $message = (new MailMessage)
            ->subject(__('Order Update') . ': #' . $this->order->order_number)
            ->greeting(__('Hello') . ' ' . $notifiable->name);

        if ($this->type === 'created') {
            $message->line(__('Your order has been successfully placed.'))
                ->line(__('Order Number') . ': ' . $this->order->order_number)
                ->line(__('Total') . ': ' . $this->order->total . ' SAR');
        } else {
            $message->line(__('Your order status has been updated to') . ': ' . $statusLabel);
        }

        return $message->action(__('View Order'), $url)
            ->line(__('Thank you for shopping with us!'));
    }

    public function toDatabase($notifiable): array
    {
        $statusLabel = $this->order->getStatusDisplayName();
        
        if ($this->type === 'created') {
            $title = __('New Order Created');
            $message = __('Order #') . $this->order->order_number . ' ' . __('has been placed successfully.');
        } else {
            $title = __('Order Status Updated');
            $message = __('Order #') . $this->order->order_number . ' ' . __('status is now') . ' ' . $statusLabel;
        }

        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'type' => $this->type,
            'title' => $title,
            'message' => $message,
            'status' => $this->order->status,
        ];
    }
}
