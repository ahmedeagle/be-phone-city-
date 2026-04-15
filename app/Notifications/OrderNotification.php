<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
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
    protected $extraData;

    public function __construct(Order $order, string $type = 'created', array $extraData = [])
    {
        $this->order = $order;
        $this->type = $type;
        $this->extraData = $extraData;
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $statusLabel = $this->order->getStatusDisplayName();

        // Use frontend URL for users, admin URL for admins
        if ($notifiable instanceof User) {
            $frontendUrl = config('app.frontend_url', config('app.url'));
            // Use user's preferred locale or default to app locale
            $locale = $notifiable->locale ?? app()->getLocale();
            $isArabic = $locale === 'ar' || str_starts_with($locale, 'ar');
            $localePrefix = $isArabic ? '/ar' : '/en';
            $url = rtrim($frontendUrl, '/') . $localePrefix . '/myorder';
        } else {
            // For admins, use Filament admin panel route
            try {
                $url = route('filament.admin.resources.orders.view', ['record' => $this->order->id]);
            } catch (\Exception $e) {
                $url = config('app.url') . '/dashboard/orders/' . $this->order->id;
            }
        }

        $greeting = ($notifiable instanceof User)
            ? __('Hello') . ' ' . $notifiable->name
            : __('Hello') . ' ' . config('app.name');

        $message = (new MailMessage)
            ->subject(__('Order Update') . ': #' . $this->order->order_number)
            ->greeting($greeting);

        if ($this->type === 'created') {
            $message->line(__('Your order has been successfully placed.'))
                ->line(__('Order Number') . ': ' . $this->order->order_number)
                ->line(__('Subtotal') . ': ' . number_format($this->order->subtotal, 2) . ' SAR');

            if ($this->order->discount > 0) {
                $message->line(__('Discount') . ': -' . number_format($this->order->discount, 2) . ' SAR');
            }

            if ($this->order->vip_discount > 0) {
                $tierLabel = $this->order->vip_tier_label ?? 'VIP';
                $message->line(__('VIP Discount') . ' (' . $tierLabel . '): -' . number_format($this->order->vip_discount, 2) . ' SAR');
            }

            if ($this->order->points_discount > 0) {
                $message->line(__('Points Discount') . ': -' . number_format($this->order->points_discount, 2) . ' SAR');
            }

            if ($this->order->shipping > 0) {
                $message->line(__('Shipping') . ': ' . number_format($this->order->shipping, 2) . ' SAR');
            }

            $message->line(__('Total') . ': ' . number_format($this->order->total, 2) . ' SAR');

            // Add branch info for store pickup orders
            if ($this->order->delivery_method === \App\Models\Order::DELIVERY_STORE_PICKUP && $this->order->branch) {
                $branch = $this->order->branch;
                $message->line('')
                    ->line(__('Pickup Branch') . ': ' . $branch->name)
                    ->line(__('Address') . ': ' . $branch->address);
                if ($branch->phone) {
                    $message->line(__('Phone') . ': ' . $branch->phone);
                }
                if ($branch->working_hours) {
                    $message->line(__('Working Hours') . ': ' . $branch->working_hours);
                }
            }
        } elseif ($this->type === 'ready_for_pickup') {
            $message->line('🎉 ' . __('Your order is ready for pickup!'))
                ->line(__('Order Number') . ': ' . $this->order->order_number)
                ->line(__('Total') . ': ' . $this->order->total . ' SAR');

            if ($this->order->branch) {
                $branch = $this->order->branch;
                $message->line('')
                    ->line('📍 ' . __('Pickup Branch') . ': ' . $branch->name)
                    ->line(__('Address') . ': ' . $branch->address);
                if ($branch->phone) {
                    $message->line('📞 ' . __('Phone') . ': ' . $branch->phone);
                }
                if ($branch->working_hours) {
                    $message->line('🕐 ' . __('Working Hours') . ': ' . $branch->working_hours);
                }
            }
        } elseif ($this->type === 'delivery_failed') {
            $failureLabel = $this->extraData['failure_label'] ?? __('Delivery failed');
            $isPermanent = $this->extraData['is_permanent'] ?? false;
            $message->line(__('We are sorry, we could not deliver your order.'))
                ->line(__('Order Number') . ': ' . $this->order->order_number)
                ->line(__('Reason') . ': ' . $failureLabel);
            if ($isPermanent) {
                $message->line(__('Our team will contact you soon to arrange an alternative.'));
            } else {
                $message->line(__('A new delivery attempt will be made soon.'));
            }
        } elseif ($this->type === 'shipment_creation_failed') {
            $errorMessage = $this->extraData['error'] ?? __('Unknown error');
            $message->line('⚠️ ' . __('Automatic shipment creation failed for this order.'))
                ->line(__('Order Number') . ': ' . $this->order->order_number)
                ->line(__('Error') . ': ' . $errorMessage)
                ->line(__('Please create the shipment manually from the admin panel.'));
        } else {
            $message->line(__('Your order status has been updated to') . ': ' . $statusLabel);

            // Add tracking info when order is shipped
            if ($this->order->tracking_number) {
                $message->line('')
                    ->line('📦 ' . __('Tracking Number') . ': ' . $this->order->tracking_number);
                if ($this->order->shipping_eta) {
                    $message->line('📅 ' . __('Expected Delivery') . ': ' . $this->order->shipping_eta);
                }
                if ($this->order->tracking_url) {
                    $message->line('');
                }
            }
        }

        // If there's a tracking URL, add it as primary action instead of My Orders
        if ($this->type === 'status_updated' && $this->order->tracking_url) {
            return $message
                ->action('📦 ' . __('Track Your Shipment'), $this->order->tracking_url)
                ->line('')
                ->line('[' . __('View Order') . '](' . $url . ')')
                ->line(__('Thank you for shopping with us!'));
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
            $typeLabel = __('New Order');
        } elseif ($this->type === 'ready_for_pickup') {
            $branchName = $this->order->branch?->name ?? '';
            $title = __('Order Ready for Pickup');
            $message = __('Order #') . $this->order->order_number . ' ' . __('is ready for pickup from') . ' ' . $branchName;
            $typeLabel = __('Ready for Pickup');
        } elseif ($this->type === 'delivery_failed') {
            $failureLabel = $this->extraData['failure_label'] ?? __('Delivery failed');
            $isPermanent = $this->extraData['is_permanent'] ?? false;
            $title = $isPermanent ? __('Delivery Failed') : __('Delivery Attempt Failed');
            $message = __('Order #') . $this->order->order_number . ' - ' . $failureLabel;
            $typeLabel = __('Delivery Failed');
        } elseif ($this->type === 'shipment_creation_failed') {
            $title = __('Shipment Creation Failed');
            $message = __('Order #') . $this->order->order_number . ' - ' . ($this->extraData['error'] ?? __('Unknown error'));
            $typeLabel = __('Shipment Error');
        } else {
            $title = __('Order Status Updated');
            $message = __('Order #') . $this->order->order_number . ' ' . __('status is now') . ' ' . $statusLabel;
            $typeLabel = __('Status Update');
        }

        $data = [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'type' => $this->type,
            'type_label' => $typeLabel,
            'title' => $title,
            'message' => $message,
            'status' => $this->order->status,
            'status_label' => $statusLabel,
        ];

        // Add tracking info to notification data
        if ($this->order->tracking_number) {
            $data['tracking_number'] = $this->order->tracking_number;
        }
        if ($this->order->tracking_url) {
            $data['tracking_url'] = $this->order->tracking_url;
        }
        if ($this->order->shipping_eta) {
            $data['shipping_eta'] = $this->order->shipping_eta;
        }

        // Add branch info for store pickup orders
        if ($this->order->delivery_method === \App\Models\Order::DELIVERY_STORE_PICKUP && $this->order->branch) {
            $data['branch_id'] = $this->order->branch->id;
            $data['branch_name'] = $this->order->branch->name;
        }

        // Add frontend URL for users in database notification
        if ($notifiable instanceof User) {
            $frontendUrl = config('app.frontend_url', config('app.url'));
            $locale = $notifiable->locale ?? app()->getLocale();
            $isArabic = $locale === 'ar' || str_starts_with($locale, 'ar');
            $localePrefix = $isArabic ? '/ar' : '/en';
            $data['url'] = rtrim($frontendUrl, '/') . $localePrefix . '/myorder';
        }

        return $data;
    }
}
