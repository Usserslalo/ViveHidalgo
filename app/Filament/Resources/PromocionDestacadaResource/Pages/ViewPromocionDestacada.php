<?php

namespace App\Filament\Resources\PromocionDestacadaResource\Pages;

use App\Filament\Resources\PromocionDestacadaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPromocionDestacada extends ViewRecord
{
    protected static string $resource = PromocionDestacadaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar Promoción'),
            Actions\Action::make('toggle_active')
                ->label('Cambiar Estado')
                ->icon('heroicon-o-power')
                ->color('warning')
                ->action(function () {
                    $this->record->update(['is_active' => !$this->record->is_active]);
                })
                ->requiresConfirmation()
                ->modalHeading('¿Cambiar estado de la promoción?')
                ->modalDescription(fn () => $this->record->is_active 
                    ? '¿Desactivar esta promoción?' 
                    : '¿Activar esta promoción?')
                ->modalSubmitActionLabel('Sí, cambiar'),
        ];
    }
} 