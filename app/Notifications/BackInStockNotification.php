<?php

namespace App\Notifications;

use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BackInStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Product $product;
    protected ?ProductOption $productOption;

    public function __construct(Product $product, ?ProductOption $productOption = null)
    {
        $this->product = $product;
        $this->productOption = $productOption;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $productName = $this->product->name_ar ?: $this->product->name_en;
        $optionLabel = '';
        if ($this->productOption) {
            $optionLabel = ' - ' . ($this->productOption->value_ar ?: $this->productOption->value_en);
        }

        $frontendUrl = config('app.frontend_url', config('app.url'));
        $productUrl = rtrim($frontendUrl, '/') . '/ar/products/' . $this->product->slug;

        return (new MailMessage)
            ->subject('🎉 المنتج عاد للمخزون! - ' . $productName)
            ->greeting('مرحباً!')
            ->line('يسعدنا إخبارك أن المنتج الذي كنت تنتظره أصبح متوفراً الآن:')
            ->line('**' . $productName . $optionLabel . '**')
            ->action('تسوق الآن', $productUrl)
            ->line('سارع بالطلب قبل نفاد الكمية!')
            ->salutation('فريق ' . config('app.name'));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'back_in_stock',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name_ar ?: $this->product->name_en,
            'product_slug' => $this->product->slug,
            'product_image' => $this->product->main_image,
            'product_option_id' => $this->productOption?->id,
            'option_label' => $this->productOption ? ($this->productOption->value_ar ?: $this->productOption->value_en) : null,
            'message_ar' => 'المنتج "' . ($this->product->name_ar ?: $this->product->name_en) . '" عاد للمخزون!',
            'message_en' => 'Product "' . ($this->product->name_en ?: $this->product->name_ar) . '" is back in stock!',
        ];
    }
}
