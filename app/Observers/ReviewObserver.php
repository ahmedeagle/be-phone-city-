<?php

namespace App\Observers;

use App\Models\Review;
use App\Services\NotificationService;

class ReviewObserver
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Handle the Review "created" event.
     */
    public function created(Review $review): void
    {
        $this->notificationService->notifyReviewCreated($review);
    }
}
