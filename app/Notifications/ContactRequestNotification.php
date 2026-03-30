<?php

namespace App\Notifications;

use App\Models\ContactRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $contactRequest;

    public function __construct(ContactRequest $contactRequest)
    {
        $this->contactRequest = $contactRequest;
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        try {
            $url = route('filament.admin.resources.contact-requests.view', ['record' => $this->contactRequest->id]);
        } catch (\Exception $e) {
            $url = config('app.url') . '/admin/contact-requests';
        }

        return (new MailMessage)
            ->subject(__('New Contact Request'))
            ->greeting(__('Hello') . ' ' . config('app.name'))
            ->line(__('A new contact request has been received.'))
            ->line(__('From') . ': ' . $this->contactRequest->name)
            ->line(__('Email') . ': ' . $this->contactRequest->email)
            ->line(__('Message') . ': ' . $this->contactRequest->message)
            ->action(__('View Request'), $url);
    }

    public function toDatabase($notifiable): array
    {
        try {
            $url = route('filament.admin.resources.contact-requests.view', ['record' => $this->contactRequest->id]);
        } catch (\Exception $e) {
            $url = null;
        }

        return [
            'contact_request_id' => $this->contactRequest->id,
            'name' => $this->contactRequest->name,
            'email' => $this->contactRequest->email,
            'title' => __('New Contact Request'),
            'message' => __('New contact request from') . ' ' . $this->contactRequest->name,
            'url' => $url,
        ];
    }
}
