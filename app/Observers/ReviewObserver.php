<?php

namespace App\Observers;

use App\Models\Review;

class ReviewObserver
{
    public function created(Review $review)
    {
        $review->destino->updateReviewStats();
    }

    public function updated(Review $review)
    {
        $review->destino->updateReviewStats();
    }

    public function deleted(Review $review)
    {
        $review->destino->updateReviewStats();
    }
} 