
<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminOtpNotification extends Notification
{
    public function __construct(
        protected string $code,
        protected string $name,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Verification Code - Admin Panel'))
            ->greeting(__('Hello') . ' ' . $this->name . '!')
            ->line(__('Your verification code is:'))
            ->line('**' . $this->code . '**')
            ->line(__('Valid for 10 minutes.'))
            ->line(__('If you did not request this code, please ignore this email.'));
    }
}
