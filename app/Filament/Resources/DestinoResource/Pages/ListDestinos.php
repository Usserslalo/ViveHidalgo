<?php

namespace App\Filament\Resources\DestinoResource\Pages;

use App\Filament\Resources\DestinoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDestinos extends ListRecords
{
    protected static string $resource = DestinoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
} 