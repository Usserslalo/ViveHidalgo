<?php

namespace App\Filament\Resources\PromocionDestacadaResource\Pages;

use App\Filament\Resources\PromocionDestacadaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditPromocionDestacada extends EditRecord
{
    protected static string $resource = PromocionDestacadaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Ver Promoción'),
            Actions\DeleteAction::make()
                ->label('Eliminar Promoción'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        Notification::make()
            ->title('Promoción actualizada exitosamente')
            ->success()
            ->send();
    }
} 