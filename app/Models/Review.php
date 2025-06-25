<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Observers\ReviewObserver;
use App\Policies\ReviewPolicy;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'destino_id',
        'rating',
        'comment',
        'is_approved',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'rating' => 'integer',
    ];

    protected static function booted()
    {
        static::observe(ReviewObserver::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function destino()
    {
        return $this->belongsTo(Destino::class);
    }

    /**
     * Get the reports for this review.
     */
    public function reports()
    {
        return $this->hasMany(ReviewReport::class);
    }

    /**
     * Get the pending reports for this review.
     */
    public function pendingReports()
    {
        return $this->hasMany(ReviewReport::class)->pending();
    }

    /**
     * Check if this review has any pending reports.
     */
    public function hasPendingReports(): bool
    {
        return $this->pendingReports()->exists();
    }

    /**
     * Get the count of pending reports.
     */
    public function getPendingReportsCountAttribute(): int
    {
        return $this->pendingReports()->count();
    }
}
