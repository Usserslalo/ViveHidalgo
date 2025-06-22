<?php

namespace App\Filament\Resources\CaracteristicaResource\Pages;

use App\Filament\Resources\CaracteristicaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCaracteristica extends EditRecord
{
    protected static string $resource = CaracteristicaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
} 