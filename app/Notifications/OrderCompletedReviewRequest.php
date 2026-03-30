<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCompletedReviewRequest extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;
    protected $products;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, $products)
    {
        $this->order = $order;
        $this->products = $products;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = config('app.url');
        // Use user's preferred locale or default to app locale
        $locale = $notifiable->locale ?? app()->getLocale();
        $isArabic = $locale === 'ar' || str_starts_with($locale, 'ar');
        $localePrefix = $isArabic ? '/ar' : '/en';

        // Build product review links
        $productLinks = [];
        foreach ($this->products as $product) {
            $productName = $isArabic ? $product->name_ar : $product->name_en;
            $productUrl = rtrim($frontendUrl, '/') . $localePrefix . '/singleproduct/' . $product->slug . '#reviews';
            $productLinks[] = [
                'name' => $productName,
                'url' => $productUrl,
            ];
        }

        $message = (new MailMessage)
            ->subject($isArabic
                ? 'طلب مراجعة المنتجات - طلب #' . $this->order->order_number
                : 'Product Review Request - Order #' . $this->order->order_number
            )
            ->greeting($isArabic
                ? 'مرحباً ' . $notifiable->name . ' 👋'
                : 'Hello ' . $notifiable->name . ' 👋'
            );

        if ($isArabic) {
            $message->line('شكراً لك على تسوقك معنا!')
                ->line('تم إكمال طلبك بنجاح #' . $this->order->order_number)
                ->line('نود أن نسمع رأيك في المنتجات التي اشتريتها. مراجعتك تساعدنا على تحسين خدماتنا وتساعد العملاء الآخرين في اتخاذ قراراتهم.')
                ->line('المنتجات التي يمكنك مراجعتها:');
        } else {
            $message->line('Thank you for shopping with us!')
                ->line('Your order #' . $this->order->order_number . ' has been completed successfully.')
                ->line('We would love to hear your feedback on the products you purchased. Your review helps us improve our services and helps other customers make informed decisions.')
                ->line('Products you can review:');
        }

        // Build product links HTML
        $productLinksHtml = '';
        foreach ($productLinks as $index => $product) {
            $productLinksHtml .= ($index + 1) . '. <a href="' . $product['url'] . '" style="color: #007bff; text-decoration: none;">' . htmlspecialchars($product['name']) . '</a><br>';
        }

        // Add product links as HTML
        $message->line(new \Illuminate\Support\HtmlString($productLinksHtml));

        if ($isArabic) {
            $message->line('نقدر وقتك ومراجعتك!')
                ->line('شكراً لكونك جزءاً من عائلتنا 🛍️');
        } else {
            $message->line('We appreciate your time and feedback!')
                ->line('Thank you for being part of our family 🛍️');
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'products_count' => count($this->products),
        ];
    }
}
