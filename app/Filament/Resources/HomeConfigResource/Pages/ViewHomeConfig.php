<?php

namespace App\Filament\Resources\HomeConfigResource\Pages;

use App\Filament\Resources\HomeConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewHomeConfig extends ViewRecord
{
    protected static string $resource = HomeConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar Configuración'),
            Actions\Action::make('activate')
                ->label('Activar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('¿Activar esta configuración?')
                ->modalDescription('Esta acción desactivará todas las demás configuraciones y activará esta.')
                ->modalSubmitActionLabel('Sí, activar')
                ->action(fn () => $this->record->activate())
                ->visible(fn () => !$this->record->is_active),
        ];
    }
} 