<?php

namespace App\Filament\Resources\HomeConfigResource\Pages;

use App\Filament\Resources\HomeConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditHomeConfig extends EditRecord
{
    protected static string $resource = HomeConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Ver Configuración'),
            Actions\DeleteAction::make()
                ->label('Eliminar Configuración'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        // Si esta configuración está activa, desactivar las demás
        if ($record->is_active) {
            $record->activate();
        }

        Notification::make()
            ->title('Configuración actualizada exitosamente')
            ->success()
            ->send();
    }
} 