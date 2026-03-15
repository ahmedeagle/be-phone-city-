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
            ->subject('رمز التحقق - لوحة التحكم')
            ->greeting('مرحباً ' . $this->name . '!')
            ->line('رمز التحقق الخاص بك هو:')
            ->line('**' . $this->code . '**')
            ->line('صالح لمدة 10 دقائق.')
            ->line('إذا لم تطلب هذا الرمز، يرجى تجاهل هذا البريد.');
    }
}
