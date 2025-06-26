<?php

namespace App\Filament\Resources\HomeConfigResource\Pages;

use App\Filament\Resources\HomeConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHomeConfigs extends ListRecords
{
    protected static string $resource = HomeConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nueva Configuración')
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