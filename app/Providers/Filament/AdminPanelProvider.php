<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Admin\Widgets\PaymentStatsWidget;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->resources([
                // Stripe
                \App\Filament\Admin\Resources\InvoiceResource::class,
                \App\Filament\Admin\Resources\PaymentMethodResource::class,
                \App\Filament\Admin\Resources\SubscriptionResource::class,
                // Admin Tasks (07_Admin-Tareas.md)
                \App\Filament\Resources\HomeConfigResource::class,
                \App\Filament\Resources\PromocionDestacadaResource::class,
                // Legacy
                \App\Filament\Resources\UserResource::class,
                \App\Filament\Resources\CategoriaResource::class,
                \App\Filament\Resources\RegionResource::class,
                \App\Filament\Resources\TagResource::class,
                \App\Filament\Resources\TopDestinoResource::class,
                \App\Filament\Resources\DestinoResource::class,
                \App\Filament\Resources\PromocionResource::class,
                \App\Filament\Resources\ReviewResource::class,
                \App\Filament\Resources\CaracteristicaResource::class,
                \App\Filament\Resources\AuditLogResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                PaymentStatsWidget::class,
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
