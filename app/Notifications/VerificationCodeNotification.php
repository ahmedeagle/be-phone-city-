<?php

namespace App\Notifications;

use App\Models\VerificationCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerificationCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public VerificationCode $verificationCode
    ) {}

    /**
     * Get the notification's delivery channels.
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
        $type = $this->verificationCode->type === 'email_verification'
            ? __('Email Verification')
            : __('Password Reset');

        return (new MailMessage)
            ->subject($type . ' - ' . config('app.name'))
            ->greeting(__('Hello') . ' ' . $notifiable->name . '!')
            ->line(__('Your verification code is:'))
            ->line('**' . $this->verificationCode->code . '**')
            ->line(__('This code will expire in 10 minutes.'))
            ->line(__('If you did not request this code, please ignore this email.'));
    }
}
