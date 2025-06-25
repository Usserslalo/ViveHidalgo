<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\PaymentMethod;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class PaymentStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        try {
            $currentMonth = now()->startOfMonth();
            $lastMonth = now()->subMonth()->startOfMonth();

            // Estadísticas de facturas
            $totalInvoices = Invoice::count();
            $paidInvoices = Invoice::where('status', 'paid')->count();
            $pendingInvoices = Invoice::where('status', 'open')->count();
            $overdueInvoices = Invoice::where('due_date', '<', now())
                ->where('status', '!=', 'paid')
                ->count();

            // Ingresos del mes actual
            $currentMonthRevenue = Invoice::where('status', 'paid')
                ->where('paid_at', '>=', $currentMonth)
                ->sum('amount');

            // Ingresos del mes anterior
            $lastMonthRevenue = Invoice::where('status', 'paid')
                ->where('paid_at', '>=', $lastMonth)
                ->where('paid_at', '<', $currentMonth)
                ->sum('amount');

            // Cálculo del crecimiento
            $growthPercentage = $lastMonthRevenue > 0 
                ? (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 
                : 0;

            // Suscripciones activas
            $activeSubscriptions = Subscription::where('status', 'active')->count();
            $cancelledSubscriptions = Subscription::where('status', 'cancelled')->count();

            // Métodos de pago
            $totalPaymentMethods = PaymentMethod::count();
            $defaultPaymentMethods = PaymentMethod::where('is_default', true)->count();

            return [
                Stat::make('Ingresos del Mes', '$' . number_format($currentMonthRevenue, 2))
                    ->description($growthPercentage >= 0 ? '+' . number_format($growthPercentage, 1) . '%' : number_format($growthPercentage, 1) . '%')
                    ->descriptionIcon($growthPercentage >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->color($growthPercentage >= 0 ? 'success' : 'danger'),

                Stat::make('Facturas Pagadas', $paidInvoices)
                    ->description('de ' . $totalInvoices . ' total')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('success'),

                Stat::make('Suscripciones Activas', $activeSubscriptions)
                    ->description($cancelledSubscriptions . ' canceladas')
                    ->descriptionIcon('heroicon-m-users')
                    ->color('primary'),

                Stat::make('Facturas Pendientes', $pendingInvoices)
                    ->description($overdueInvoices . ' vencidas')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color($overdueInvoices > 0 ? 'danger' : 'warning'),

                Stat::make('Métodos de Pago', $totalPaymentMethods)
                    ->description($defaultPaymentMethods . ' por defecto')
                    ->descriptionIcon('heroicon-m-credit-card')
                    ->color('info'),
            ];
        } catch (\Exception $e) {
            // En caso de error, retornar estadísticas básicas
            return [
                Stat::make('Error en Estadísticas', 'Error')
                    ->description('No se pudieron cargar las estadísticas')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('danger'),
            ];
        }
    }
} 