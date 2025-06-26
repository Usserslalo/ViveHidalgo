<?php

namespace App\Filament\Resources\PromocionDestacadaResource\Pages;

use App\Filament\Resources\PromocionDestacadaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPromocionDestacadas extends ListRecords
{
    protected static string $resource = PromocionDestacadaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nueva Promoción')
                ->icon('heroicon-o-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Aquí puedes agregar widgets si los necesitas
        ];
    }
} 