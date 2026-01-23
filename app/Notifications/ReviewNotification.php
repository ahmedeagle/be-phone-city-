<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $review;

    public function __construct(Review $review)
    {
        $this->review = $review;
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('New Product Review'))
            ->line(__('A new review has been submitted for product') . ': ' . $this->review->product->name)
            ->line(__('Rating') . ': ' . $this->review->rating . '/5')
            ->line(__('Comment') . ': ' . $this->review->comment)
            ->action(__('View Review'), config('app.url') . '/admin/reviews');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'review_id' => $this->review->id,
            'product_id' => $this->review->product_id,
            'product_name' => $this->review->product->name,
            'user_name' => $this->review->user->name,
            'rating' => $this->review->rating,
            'title' => __('New Product Review'),
            'message' => __('New review for') . ' ' . $this->review->product->name . ' ' . __('by') . ' ' . $this->review->user->name,
        ];
    }
}
