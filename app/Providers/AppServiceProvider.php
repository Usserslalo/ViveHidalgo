<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use App\Models\Review;
use App\Models\HomeConfig;
use App\Models\Evento;
use App\Models\Destino;
use App\Observers\ReviewObserver;
use App\Policies\ReviewPolicy;
use App\Policies\HomeConfigPolicy;
use App\Policies\EventoPolicy;
use App\Policies\GalleryPolicy;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
        Review::observe(ReviewObserver::class);
        Gate::policy(Review::class, ReviewPolicy::class);
        Gate::policy(HomeConfig::class, HomeConfigPolicy::class);
        Gate::policy(Evento::class, EventoPolicy::class);
        Gate::policy(Destino::class, GalleryPolicy::class);
    }
}
