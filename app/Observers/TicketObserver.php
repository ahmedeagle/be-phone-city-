<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Services\NotificationService;

class TicketObserver
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Handle the Ticket "created" event.
     */
    public function created(Ticket $ticket): void
    {

        $this->notificationService->notifyTicketCreated($ticket);
    }

    /**
     * Handle the Ticket "updated" event.
     */
    public function updated(Ticket $ticket): void
    {
        if ($ticket->isDirty('status') || $ticket->isDirty('description')) {
            $this->notificationService->notifyTicketUpdated($ticket);
        }
    }
}
