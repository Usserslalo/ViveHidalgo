<?php

namespace App\Filament\Resources\PromocionDestacadaResource\Pages;

use App\Filament\Resources\PromocionDestacadaResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreatePromocionDestacada extends CreateRecord
{
    protected static string $resource = PromocionDestacadaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        Notification::make()
            ->title('Promoción creada exitosamente')
            ->success()
            ->send();
    }
} 