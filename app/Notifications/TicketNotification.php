<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $ticket;
    protected $type;

    public function __construct(Ticket $ticket, string $type = 'created')
    {
        $this->ticket = $ticket;
        $this->type = $type;
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = config('app.url') . '/tickets/' . $this->ticket->id;
        
        $message = (new MailMessage)
            ->subject(__('Ticket Update') . ': #' . $this->ticket->ticket_number)
            ->greeting(__('Hello') . ' ' . $notifiable->name);

        if ($this->type === 'created') {
            $message->line(__('A new support ticket has been created.'))
                ->line(__('Subject') . ': ' . $this->ticket->subject);
        } else {
            $message->line(__('Your support ticket has been updated.'))
                ->line(__('Status') . ': ' . __($this->ticket->status));
        }

        return $message->action(__('View Ticket'), $url)
            ->line(__('Thank you for contacting us!'));
    }

    public function toDatabase($notifiable): array
    {
        if ($this->type === 'created') {
            $title = __('New Ticket Created');
            $message = __('Ticket #') . $this->ticket->ticket_number . ': ' . $this->ticket->subject;
        } else {
            $title = __('Ticket Updated');
            $message = __('Ticket #') . $this->ticket->ticket_number . ' ' . __('has been updated.');
        }

        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'type' => $this->type,
            'title' => $title,
            'message' => $message,
            'status' => $this->ticket->status,
        ];
    }
}
