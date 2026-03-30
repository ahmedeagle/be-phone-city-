<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $ticket;
    protected $type;
    protected $adminReply;

    public function __construct(Ticket $ticket, string $type = 'created', ?string $adminReply = null)
    {
        $this->ticket = $ticket;
        $this->type = $type;
        $this->adminReply = $adminReply;
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        // Use frontend_url for users, admin URL for admins
        if ($notifiable instanceof User) {
            $frontendUrl = config('app.frontend_url', config('app.url'));
            // Use user's preferred locale or default to app locale
            $locale = $notifiable->locale ?? app()->getLocale();
            $isArabic = $locale === 'ar' || str_starts_with($locale, 'ar');
            $localePrefix = $isArabic ? '/ar' : '/en';
            $url = rtrim($frontendUrl, '/') . $localePrefix . '/tickets';
        } else {
            // For admins, use Filament admin panel URL
            try {
                $url = route('filament.admin.resources.tickets.view', ['record' => $this->ticket->id]);
            } catch (\Throwable $e) {
                $url = rtrim(config('app.url'), '/') . '/dashboard/tickets/' . $this->ticket->id;
            }
        }

        $greeting = ($notifiable instanceof User)
            ? __('Hello') . ' ' . $notifiable->name
            : __('Hello') . ' ' . config('app.name');

        $message = (new MailMessage)
            ->subject(__('Ticket Update') . ': #' . $this->ticket->ticket_number)
            ->greeting($greeting);

        if ($this->type === 'created') {
            $message->line(__('A new support ticket has been created.'))
                ->line(__('Subject') . ': ' . $this->ticket->subject);
        } elseif ($this->type === 'replied') {
            $message->line(__('You have received a reply on your support ticket.'))
                ->line(__('Subject') . ': ' . $this->ticket->subject);
            if ($this->adminReply) {
                $message->line('---')
                    ->line($this->adminReply)
                    ->line('---');
            }
        } else {
            $statusLabel = $this->ticket->status_label;
            $message->line(__('Your support ticket has been updated.'))
                ->line(__('Status') . ': ' . $statusLabel);
        }

        return $message->action(__('View Ticket'), $url)
            ->line(__('Thank you for contacting us!'));
    }

    public function toDatabase($notifiable): array
    {
        if ($this->type === 'created') {
            $title = __('New Ticket Created');
            $message = __('Ticket #') . $this->ticket->ticket_number . ': ' . $this->ticket->subject;
        } elseif ($this->type === 'replied') {
            $title = __('Ticket Reply Received');
            $message = __('Ticket #') . $this->ticket->ticket_number . ' ' . __('has received a reply.');
        } else {
            $statusLabel = $this->ticket->status_label;
            $title = __('Ticket Status Updated');
            $message = __('Ticket #') . $this->ticket->ticket_number . ' - ' . __('Status') . ': ' . $statusLabel;
        }

        $data = [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'type' => $this->type,
            'type_label' => $this->type === 'created' ? __('New Ticket') : __('Ticket Update'),
            'title' => $title,
            'message' => $message,
            'status' => $this->ticket->status,
            'status_label' => __($this->ticket->status),
        ];

        // Add frontend URL for users in database notification
        if ($notifiable instanceof User) {
            $frontendUrl = config('app.frontend_url', config('app.url'));
            $locale = $notifiable->locale ?? app()->getLocale();
            $isArabic = $locale === 'ar' || str_starts_with($locale, 'ar');
            $localePrefix = $isArabic ? '/ar' : '/en';
            $data['url'] = rtrim($frontendUrl, '/') . $localePrefix . '/tickets';
        }

        return $data;
    }
}
