<?php

namespace App\Filament\Resources\HomeConfigResource\Pages;

use App\Filament\Resources\HomeConfigResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateHomeConfig extends CreateRecord
{
    protected static string $resource = HomeConfigResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        // Si esta configuración está activa, desactivar las demás
        if ($record->is_active) {
            $record->activate();
        }

        Notification::make()
            ->title('Configuración creada exitosamente')
            ->success()
            ->send();
    }
} 