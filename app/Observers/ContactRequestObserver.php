<?php

namespace App\Observers;

use App\Models\ContactRequest;
use App\Services\NotificationService;

class ContactRequestObserver
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Handle the ContactRequest "created" event.
     */
    public function created(ContactRequest $contactRequest): void
    {
        $this->notificationService->notifyContactRequestCreated($contactRequest);
    }
}
