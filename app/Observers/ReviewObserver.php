<?php

namespace App\Observers;

use App\Models\Review;
use App\Notifications\ReviewApproved;
use App\Notifications\ReviewRejected;

class ReviewObserver
{
    public function created(Review $review)
    {
        $review->destino->updateReviewStats();
    }

    public function updated(Review $review)
    {
        $review->destino->updateReviewStats();

        // Notificación de aprobación/rechazo
        if ($review->isDirty('is_approved')) {
            $original = $review->getOriginal('is_approved');
            $nuevo = $review->is_approved;

            if (!$original && $nuevo) {
                // Fue aprobada
                $review->user?->notify(new ReviewApproved($review));
            } elseif ($original && !$nuevo) {
                // Fue rechazada (o des-aprobada)
                $review->user?->notify(new ReviewRejected($review));
            }
        }
    }

    public function deleted(Review $review)
    {
        $review->destino->updateReviewStats();
    }
} 