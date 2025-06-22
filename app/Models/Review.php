<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Observers\ReviewObserver;
use App\Policies\ReviewPolicy;

class Review extends Model
{
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
}
